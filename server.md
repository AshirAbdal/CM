# Server Deployment Instructions

## Folder Structure Required

Both projects must sit as **siblings** under the same parent directory:

```
/var/www/
    mm-admin/
    mm-frontend/
```

---

## 1. Upload Files

Upload both folders to `/var/www/` (or your hosting root):

```bash
mm-admin/
mm-frontend/
```

---

## 2. Set File Permissions

The web server needs write access to the frontend assets folder (for image replacement):

```bash
chmod -R 775 /var/www/mm-frontend/public/assets/
chown -R www-data:www-data /var/www/mm-frontend/public/assets/
```

> Replace `www-data` with your actual web user. Check it with:
> ```bash
> ps aux | grep -E 'apache|nginx|php-fpm' | head -3
> ```

---

## 3. Web Server Config (Nginx)

Create two server blocks — one per project.

**mm-frontend** (public site):
```nginx
server {
    listen 80;
    server_name majesticmarquees.com www.majesticmarquees.com;
    root /var/www/mm-frontend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

**mm-admin** (admin panel):
```nginx
server {
    listen 80;
    server_name admin.majesticmarquees.com;
    root /var/www/mm-admin/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

---

## 4. Enable HTTPS (SSL)

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d majesticmarquees.com -d www.majesticmarquees.com
certbot --nginx -d admin.majesticmarquees.com
```

---

## 5. Update Allowed Hosts

In `mm-frontend/public/index.php`, update:
```php
$allowedHosts = ['majesticmarquees.com', 'www.majesticmarquees.com'];
```

In `mm-admin/public/index.php`, update:
```php
$allowedHosts = ['admin.majesticmarquees.com'];
```

---

## 6. Verify Image Manager Path

SSH into the server and run:
```bash
php -r "echo dirname('/var/www/mm-admin/pages/x', 2) . '/mm-frontend/public/assets/';"
```
Expected output:
```
/var/www/mm-frontend/public/assets/
```

---

## 7. Reload Nginx

```bash
nginx -t && systemctl reload nginx
```
