# Triggers Used to Sync Data Between fb_products and PSI_Products

This document explains the trigger defined in the migration `UpdateFbProductsTriggerAddFsvpStatusField`. The purpose of this trigger is to keep the `PSI_Products` table synchronized whenever a record in `fb_products` is updated.

---

## Trigger Name

**`fb_products_update`**

## Trigger Event

* **AFTER UPDATE** on the `fb_products` table
* Executes for **each row** that is updated

---

## Conditions Checked

The trigger runs its logic only when all of these conditions are true:

1. `NEW.status = 1` → product is active
2. `NEW.FB_Id` is not empty
3. `NEW.FB_Id` is not NULL

These conditions ensure that only valid and active FB products are synced into PSI.

---

## Internal Variables Used

* `@COUNTRECORDS` → stores whether the product exists in `PSI_Products`
* `@clientName` → fetches the client name from `crm_classes` using `client_id`

---

## Logic Overview

The trigger performs **two primary operations** based on product existence:

### 1. Update Existing Product

If `PSI_Products` already contains the product (`@COUNTRECORDS > 0`), the trigger:

* Updates pricing fields
* Updates product metadata like description, UOM, compliance, dimensions
* Updates identifiers such as UPC, SCC, brand, sub-brand info
* Updates calculated fields (`product_id_14`, `product_id_14_pre`)
* Updates FSVP status (in the `up` migration version)
* Refreshes timestamps (`LastUpdated`, `TimeModified`)

This ensures PSI always reflects the latest FB product information.

### 2. Insert New Product

If the product does **not** exist in PSI (`@COUNTRECORDS = 0`), the trigger inserts a **new row** with:

* Product IDs and identifiers
* Pricing and compliance values
* Packaging and dimensional info
* Brand and sub-brand fields
* Status flags such as `activeflag`
* FSVP status (in the updated migration)
* Client name fetched from `crm_classes`

This ensures PSI receives new product entries automatically.

---

## FSVP Field Addition

The new migration extends both the UPDATE and INSERT sections to include:

* `fsvp_status = NEW.fsvp_status`

This ensures FSVP compliance information is synced into PSI for reporting and downstream processes.

---

## Extracted Trigger (from Migration – Up Version)

```
CREATE TRIGGER fb_products_update
AFTER UPDATE ON `fb_products` FOR EACH ROW
BEGIN
    IF (NEW.status = 1 AND NEW.FB_Id <> '' AND NEW.FB_Id IS NOT NULL)  THEN
        SET @COUNTRECORDS =  (select count(product_id) AS count from PSI_Products where product_id =  NEW.product_id LIMIT 1);
        SET @clientName = (select name from crm_classes where id = New.client_id);

        IF (@COUNTRECORDS > 0) then
            UPDATE PSI_Products
            set ... , fsvp_status = NEW.fsvp_status
            where product_id = NEW.product_id;
        END IF;

        IF (@COUNTRECORDS = 0) then
            Insert into PSI_Products (..., fsvp_status)
            VALUES (..., New.fsvp_status);
        END IF;
    END IF;
END
```

---

## Purpose of This Trigger

This trigger ensures that **any update** on `fb_products` automatically synchronizes the corresponding record in `PSI_Products`. It prevents mismatched product data between systems by:

* Updating existing PSI entries when product details change
* Creating new PSI entries when a new FB product appears
* Syncing FSVP compliance details

This automation eliminates manual syncing and ensures both systems stay aligned.
