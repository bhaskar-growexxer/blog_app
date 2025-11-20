# **MongoDB Setup for Laravel (Ubuntu)**
This guide helps you install the MongoDB PHP extension, enable it, and install the Laravel MongoDB library (`jenssegers/mongodb`) with Composer.

---

## **1. Install PHP MongoDB Extension**

Update system packages:

```bash
sudo apt-get update
```

Install the MongoDB extension for PHP:

```bash
sudo apt-get install php-mongodb
```

Enable the extension:

```bash
sudo phpenmod mongodb
```

---

## **2. Restart Web Server**

### **If using Apache**

```bash
sudo systemctl restart apache2
```

### **If using PHP-FPM (Nginx users)**

```bash
sudo systemctl restart php-fpm
```

> If you have multiple PHP versions, restart the specific version:

```bash
sudo systemctl restart php8.2-fpm
```

---

## **3. Verify MongoDB Extension**

Check whether PHP recognizes the MongoDB extension:

```bash
php -m | grep mongodb
```

If it prints:

```
mongodb
```

then the extension is correctly installed.

---
## **4. Install Laravel MongoDB Library**

Finally, install the MongoDB package:

```bash
composer2 require jenssegers/mongodb
```

This provides:

* MongoDB Eloquent model (`Jenssegers\Mongodb\Eloquent\Model`)
* Mongodb DB connection support
* Full CRUD and aggregation support

---

## ✔ **Setup Complete**

You now have:

* MongoDB PHP extension installed
* PHP-FPM/Apache restarted
* Composer 2 available
* Laravel MongoDB library installed

---

