# Translating All Purpose Directory

Thank you for your interest in translating All Purpose Directory! This document explains how to contribute translations to the plugin.

## Overview

- **Text Domain:** `all-purpose-directory`
- **POT File:** `languages/all-purpose-directory.pot`
- **Total Strings:** ~938 translatable strings

## How to Translate

### Option 1: Using Poedit (Recommended)

1. Download and install [Poedit](https://poedit.net/) (free version works fine)
2. Open Poedit and select **File > New from POT/PO File**
3. Choose the `languages/all-purpose-directory.pot` file
4. Select your target language when prompted
5. Translate the strings in the editor
6. Save the file as `all-purpose-directory-{locale}.po` (e.g., `all-purpose-directory-fr_FR.po`)
7. Poedit will automatically generate the `.mo` file

### Option 2: Using Loco Translate (WordPress Plugin)

1. Install and activate the [Loco Translate](https://wordpress.org/plugins/loco-translate/) plugin
2. Go to **Loco Translate > Plugins** in your WordPress admin
3. Click on **All Purpose Directory**
4. Click **New language** and select your language
5. Translate the strings directly in WordPress
6. Click **Save** when finished

### Option 3: Using translate.wordpress.org

If All Purpose Directory is hosted on WordPress.org, you can contribute translations through the official [translate.wordpress.org](https://translate.wordpress.org/) platform.

## File Naming Convention

Translation files must follow this naming pattern:

```
all-purpose-directory-{locale}.po   # Human-readable translation file
all-purpose-directory-{locale}.mo   # Compiled binary file (auto-generated)
```

### Common Locale Codes

| Language | Locale Code | Files |
|----------|-------------|-------|
| French | fr_FR | `all-purpose-directory-fr_FR.po` |
| German | de_DE | `all-purpose-directory-de_DE.po` |
| Spanish | es_ES | `all-purpose-directory-es_ES.po` |
| Italian | it_IT | `all-purpose-directory-it_IT.po` |
| Dutch | nl_NL | `all-purpose-directory-nl_NL.po` |
| Portuguese (Brazil) | pt_BR | `all-purpose-directory-pt_BR.po` |
| Japanese | ja | `all-purpose-directory-ja.po` |
| Chinese (Simplified) | zh_CN | `all-purpose-directory-zh_CN.po` |

## Translation Guidelines

### General Tips

1. **Keep placeholders intact** - Strings with `%s`, `%d`, `%1$s`, etc. should keep these placeholders in the translation
2. **Preserve HTML** - Keep any HTML tags like `<strong>`, `<a>`, etc. in your translation
3. **Match context** - Pay attention to translator comments (lines starting with `#.`) for context
4. **Be consistent** - Use the same translation for recurring terms throughout the plugin

### Context-Aware Translations

Some strings include context to help disambiguate meanings. In POT files, these appear as:

```
msgctxt "post type general name"
msgid "Listings"
msgstr ""
```

The context (`post type general name`) tells you this "Listings" is used as a general name for the post type, not a verb or other usage.

### Plural Forms

WordPress uses `ngettext` for plurals. These appear as:

```
msgid "%d review awaiting moderation"
msgid_plural "%d reviews awaiting moderation"
msgstr[0] ""
msgstr[1] ""
```

Different languages have different plural rules. Poedit handles this automatically based on your language.

## Updating Translations

When a new version of the plugin is released:

1. Regenerate the POT file (developers): `npm run i18n:pot`
2. Open your existing `.po` file in Poedit
3. Select **Catalog > Update from POT File**
4. Choose the updated `all-purpose-directory.pot`
5. Translate new/changed strings
6. Save to regenerate the `.mo` file

## Contributing Translations

### For Developers

To regenerate the POT file after code changes:

```bash
npm run i18n:pot
```

This extracts all translatable strings from:
- `src/**/*.php`
- `includes/**/*.php`
- `templates/**/*.php`
- `all-purpose-directory.php`

### For Translators

To contribute your translation:

1. Fork the repository on GitHub
2. Add your `.po` and `.mo` files to the `languages/` folder
3. Submit a Pull Request

Or email your translation files to the plugin author.

## String Statistics

The plugin contains approximately:

- **938** unique translatable strings
- **122** strings with translator comments
- **18** plural forms
- Strings across **76** PHP files

Categories of strings:
- Admin interface labels
- Frontend display text
- Form fields and validation messages
- Email notification templates
- Error and success messages

## Questions?

If you have questions about translating specific strings or need context, please open an issue on the plugin's GitHub repository.

Thank you for helping make All Purpose Directory accessible to more users around the world!
