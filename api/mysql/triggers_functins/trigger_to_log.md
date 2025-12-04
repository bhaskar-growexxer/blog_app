# Trigger for Logging Changes from fb_products into product_logs

This document explains the trigger used to insert a log entry into the `product_logs` table whenever a record in `fb_products` is updated. This trigger is designed to capture product changes for auditing or tracking purposes.

---

## Trigger Name

**`fb_products_update_logs`**

## Trigger Event

* **AFTER UPDATE** on the `fb_products` table
* Executes **once for each updated row**

---

## Purpose of the Trigger

Whenever a product is updated in `fb_products`, this trigger automatically inserts a new row into `product_logs` to capture key information such as:

* Product ID
* Old and new values
* Timestamp of the update
* Metadata identifying the change

This ensures a complete audit trail of any modifications made to FB product data.

---

## Example Trigger Logic

Below is an example trigger implementation for logging changes.

```
CREATE TRIGGER fb_products_update_logs
AFTER UPDATE ON fb_products
FOR EACH ROW
BEGIN
    INSERT INTO product_logs (
        product_id,
        fb_id,
        old_price,
        new_price,
        old_status,
        new_status,
        old_description,
        new_description,
        updated_at
    ) VALUES (
        NEW.product_id,
        NEW.FB_Id,
        OLD.price,
        NEW.price,
        OLD.status,
        NEW.status,
        OLD.description,
        NEW.description,
        NOW()
    );
END;
```

---

## Explanation of Inserted Fields

### **product_id**

Captured from `NEW.product_id` to identify which product was modified.

### **fb_id**

Provides the associated FB identifier for reference.

### **Old and New Values**

The trigger stores the old and new values for important fields such as:

* **price** → useful for pricing audit
* **status** → helps track activation/deactivation
* **description** → logs changes in product details

### **updated_at**

Uses `NOW()` to record the exact time the update occurred.

---

## How This Logging Helps

* Maintains a historical record of changes
* Helps in debugging issues when product values appear incorrect
* Supports compliance and auditing requirements
* Enables analytics on how product details evolve over time

This logging trigger ensures that every significant update in `fb_products` is traceable and recoverable through the `product_logs` table.
