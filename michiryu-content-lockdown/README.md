# MichiRyu Static Content Lockdown

Use these files carefully. The API folder and static content folder need different `.htaccess` files.

## Folder Map

API folder:

```text
public_html/michiryu-content-api
```

Static content folder:

```text
public_html/michiryu-content
```

## If The API Shows Forbidden

If this URL shows `Forbidden`:

```text
https://www.bowerr31x.com/michiryu-content-api/index.php?route=health
```

then the static lockdown file was probably placed in `michiryu-content-api` by mistake.

Fix it by replacing:

```text
public_html/michiryu-content-api/.htaccess
```

with the contents of:

```text
api-htaccess-restore.txt
```

Then test:

```text
https://www.bowerr31x.com/michiryu-content-api/index.php?route=health
```

Expected: JSON with `"status": "ok"`.

## Lock Down Static Content

Only after the API health endpoint works, upload:

```text
static-content-htaccess-lockdown.txt
```

to:

```text
public_html/michiryu-content
```

Then rename it to:

```text
.htaccess
```

Test direct static access. This should be blocked:

```text
https://www.bowerr31x.com/michiryu-content/featured-content.json?test=1
https://www.bowerr31x.com/michiryu-content/images/map/yuki-no-sato-sekki-map.jpg?test=1
```

Test API access. This should still work:

```text
https://www.bowerr31x.com/michiryu-content-api/index.php?route=health
```

Then test WordPress Basic Import.

Expected result:

```text
Imported 72 stories, 31 characters, and 61 image references.
```

## Rollback

To undo static folder lockdown, delete:

```text
public_html/michiryu-content/.htaccess
```
