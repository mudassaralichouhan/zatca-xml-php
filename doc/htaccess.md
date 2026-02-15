```htaccess
# Turn on rewrite
RewriteEngine On
RewriteBase /

# Prevent directory listing
Options -Indexes

# Protect .env and other sensitive files
<FilesMatch "^(\.env|composer\.(json|lock)|\.gitignore|\.htpasswd)">
  Require all denied
  # For older Apache 2.2:
  # Order allow,deny
  # Deny from all
</FilesMatch>

# Prevent access to vendor files that shouldn't be direct (optional)
# Allow direct access to vendor assets if you need them (fonts/css) — otherwise deny
<IfModule mod_alias.c>
  RedirectMatch 403 ^/vendor/(?!your-allowed-path).*$
</IfModule>

# If the requested file or directory exists, serve it directly
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Otherwise forward everything to index.php (front controller)
# Keep query string (QSA) and stop processing (L)
RewriteRule ^ index.php [QSA,L]
```

```
# .htaccess - place in document root (htdocs / public_html)

# Enable URL rewriting
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /

  # If request is for an existing file or directory, serve it
  RewriteCond %{REQUEST_FILENAME} -f [OR]
  RewriteCond %{REQUEST_FILENAME} -d
  RewriteRule ^ - [L]

  # Otherwise, route everything to index.php (front controller)
  RewriteRule ^ index.php [QSA,L]
</IfModule>

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^(\.env|composer\.(json|lock)|\.gitignore|\.htpasswd)$">
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
  <IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
  </IfModule>
</FilesMatch>

# Block direct access to vendor except allowed assets
<IfModule mod_alias.c>
  RedirectMatch 403 ^/vendor/(?!your-allowed-path).*
</IfModule>

# Add basic CORS headers (sometimes hosting allows this, sometimes not)
<IfModule mod_headers.c>
  Header set Access-Control-Allow-Origin "*"
  Header set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
  Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, X-API-KEY, Zatca-Mode"
</IfModule>
```