# Plan: per-user language for the dashboard and all plugin text (v1.27)

Goal: the client dashboard (and every other screen the plugin renders) automatically
appears in the language of the logged-in admin user, with all plugin text translating
wherever translation is possible.

## The key insight: WordPress already does the per-user part

Since WP 4.7, every user can pick their own admin language (Users → Profile →
Language), and WordPress resolves every gettext call in wp-admin against that user
locale (`get_user_locale()`) automatically — including admin-ajax requests such as the
support modal submit. **No custom locale-switching machinery is needed.**

The only reason this plugin renders English for everyone is that none of its strings
go through gettext. The plumbing half-exists — `Text Domain: wp-agency-admin-toolkit`
is declared, `Domain Path: /languages` is set, and `Core::load_textdomain()` runs on
`init` (correct timing for WP 6.7+) — but an audit shows **zero** `__()` /
`esc_html__()` calls in the codebase and no `languages/` directory. So the work is
classic internationalisation: wrap every string, ship translation files, and the
per-user behaviour falls out for free. Users who never pick a profile language get the
site language — also automatic.

## Two kinds of text — and what "where possible" means

1. **Code strings** (headings, labels, buttons, notices, statuses, empty states,
   emails): fully translatable via gettext. This is ~95% of visible text.
2. **Operator content stored in settings** (dashboard title, welcome message,
   instruction boxes, shortcut labels, support categories, support button label,
   admin footer text): these are whatever the agency typed and live in the database.
   Gettext cannot translate arbitrary stored content, so they follow the language the
   operator wrote them in — with one big improvement available: **untouched defaults
   can still translate per user** (see step 4).

Runtime machine translation (calling a translation API while rendering) is
deliberately out of scope: slow, costly, cache-hostile and unreviewable. Machine
translation belongs in the *translation-file workflow* (pre-translate the PO, human
review), not at runtime.

## Implementation steps

### 1. Infrastructure

- Create `languages/`. It ships in the customer ZIP automatically (the release
  workflows' rsync exclude list doesn't touch it).
- Keep the existing text domain `wp-agency-admin-toolkit` — it must stay in sync with
  `load_plugin_textdomain()`, and since no translations exist yet there's nothing to
  orphan. Every gettext call must use the **literal** domain string (extractors can't
  resolve constants).
- POT generation via WP-CLI: `wp i18n make-pot . languages/wp-agency-admin-toolkit.pot`.
  Compile with `wp i18n make-mo languages` plus `wp i18n make-php languages` for the
  WP 6.5+ performance format. File naming matters:
  `wp-agency-admin-toolkit-nl_NL.mo` etc.
- Document the regeneration commands in RELEASE-WORKFLOW.md; optionally add a CI step
  that regenerates the POT and fails when it drifts from the committed one.

### 2. Wrap all PHP strings (the bulk of the work, ~250–300 strings)

File-by-file, using the escaping-aware variants (`esc_html__()` / `esc_attr__()` when
echoing into markup, `__()` for values passed to WP APIs that escape themselves),
`sprintf()` with numbered placeholders instead of concatenation, `_n()` for plurals,
and translator comments on any string with a placeholder:

| File | What |
|---|---|
| `src/Dashboard.php` | **The client dashboard** — panel headings (Site snapshot, Common tasks, Recent orders, Recently edited content, Support, Client instructions), Log out, snapshot labels (Published pages, Media files, Published products, Processing orders), widget titles (Website Shortcuts, Agency Support, Store Snapshot), empty states, `Order #%s`, `(no title)`, the Tip line |
| `src/Support.php` | Modal (Request Support, Category, Priority, Subject, Message, Send Request, consent line), AJAX responses (sent / saved as ticket #%d / error / please log in / please add a message), email body labels |
| `src/Admin.php` + `src/Admin/*.php` | Menu titles, `pages_nav()` labels, every card heading, field label, description, button, notice, confirm() text, licence status words (Active / Not active / Not activated, Expires), System Status row labels, ticket table headers, bulk action labels, search placeholder |
| `src/Tickets.php` | Status labels — the `STATUSES` const can't hold `__()` calls, so convert to a static `statuses()` method returning translated labels at call time while the stored keys (`new`, `in_progress`, …) stay stable. `%d ticket updated` → `_n()` |
| `src/Cleanup.php` | The "Protected agency setting" `wp_die()` block and Return to dashboard |
| `src/Branding.php` | `Managed by %s` fallback footer |
| `src/Licence.php` | Licence/update messages (note: some are stored into settings at save time — translating at generation time is acceptable; they re-generate on the next check) |
| `src/Core.php` | The `Client Admin` role display name (translate at registration; WP stores role names, same caveat as any plugin role) |

Same pattern for other stored-key/display-label pairs: ticket priorities
(Normal/High/Urgent keep their stored English values, get a value→label map for
display and for the modal `<select>`), dashboard layouts, webhook format names.
Dates already localise (`mysql2date` + `number_format_i18n` are in place).

### 3. JavaScript strings

`assets/js/admin.js` has a handful of user-facing strings ('Sending...', 'Sent.',
'Could not send request.', and the Label/URL/Capability placeholders in the shortcut
row template). The repo has no build tooling, so skip `wp_set_script_translations`
and extend the existing `wp_localize_script('aat-admin', 'aatSupport', …)` object
with a `strings` map translated server-side. JS reads `aatSupport.strings.sending`
etc. with English fallbacks.

### 4. Make untouched *default* content translate per user

Fresh installs save `Core::defaults()` into the option, freezing English defaults in
the database — so even translated defaults would stick in the activation-time
language. Fix without a schema change, reusing the comparison trick the plugin
already uses in `maybe_migrate_product_brand_defaults()`:

- Wrap the default content strings (welcome message, instruction texts, dashboard
  title, shortcut labels, support categories, footer text, button label) in `__()`.
- At **render time**, when a saved value is byte-identical to the untranslated
  English default, output the translated default instead (helper:
  `Core::translated_setting($key)`). Operators who customised the text get their
  exact text; operators who never touched it get per-user translation.

Optional phase 2 for genuinely multilingual client sites: register the customised
content with WPML/Polylang string translation via their filter APIs
(`wpml_register_single_string` / `wpml_translate_single_string`), guarded by
`function_exists`/`has_filter` checks so it's inert without those plugins.

### 5. Email locale

Support emails go to the agency mailbox, so they should not arrive in whatever
language the *submitting client* happens to use. Wrap email composition in
`switch_to_locale(get_locale())` / `restore_previous_locale()` so emails always use
the site's default language, while the AJAX response the client sees stays in their
own language.

### 6. Ship translations

- Start with `nl_NL` (the agency's own market), then `de_DE`, `fr_FR`, `es_ES` as
  demand dictates.
- Workflow: generate POT → machine pre-translate the PO (DeepL or similar) → human
  review → `make-mo` + `make-php` → commit.
- The login page and client dashboard are the two screens clients actually see —
  review those strings first.

### 7. Release

- Version bump to **1.27** in the usual three CI-checked places, changelog entry.
- No slugs, option keys, stored status/priority values, or file names change — the
  update pipeline is untouched.

## Testing checklist

- Install a second language (Settings → General → Site Language) so the profile
  dropdown appears; set a user's profile language to Dutch.
- As that user: client dashboard, all nine admin pages, support modal +
  submit responses, ticket list/detail, protected-page block screen — all Dutch;
  site stays English for other users.
- Matrix: site nl + user en, site en + user nl, user with no preference.
- Submit a support request as a Dutch-locale user → email arrives in site language,
  AJAX confirmation in Dutch.
- `WP_DEBUG` on: no "translation loading triggered too early" notices.
- `wp i18n make-pot` runs clean (no placeholder/domain warnings); POT string count
  roughly matches the audit (~250–300).

## Risks

- **Missed strings** — mitigate with a final `grep` for echoed literals and a POT
  diff against the audit list.
- **Frozen stored text** — statuses/priorities are safe (stored as keys); licence
  messages regenerate on the next check; default-content fallback handles the rest.
- **String freeze churn** — wrapping touches nearly every render method; do it in
  one focused release (this is v1.27's whole feature) rather than trickling across
  versions, so translators work against a stable POT.
