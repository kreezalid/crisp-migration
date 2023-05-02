# Crisp migration : HTML Articles to MD & API import

## HTML to MD conversion

### Install dependencies

```bash
composer install
```

### Prepare your database

Assuming the table already have:

-   a title column
-   a html content column
-   a description column

Create 3 columns in the table containing the articles:

-   one for the markdown content
-   one for the crisp article id
-   one for the loading datetime

Fill the `config.ini` file with your db credentials & info.

### Customize the script

If you have classes or ids in your html, you can customize the script to convert them to markdown.

The script is located in `html2md.php` and the functions are located under the `// Custom filters` comment.

### Run the conversion script

```bash
php html2md.php
```

### Check the result

You might have to check the result and correct some errors with the `search & replace` function of your database admin tool before running the import script. For example, if the language of your articles contains special characters, you might have to replace them with the correct ones.

## Crisp API import

### Get the API credentials

1. Go to <https://app.crisp.chat/website/{website_id}/inbox/>

2. Go to the console and type: `localStorage.user_session`

It return your identifier and key in this form:

```json
{
	"identifier": "a71cb627-2432-4918-9900-fd5d4boo5e84",
	"key": "834e589b45154ebef888b779c5d199af83e9094240b44bedd4d740bcf8972984"
}
```

3. Set the credentials in `config.ini`

### Run the migration script

```bash
php crisp_migrate.php
```
