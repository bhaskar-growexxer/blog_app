# MySQL Learning Document

## 1. SQL Triggers

### Overview

SQL triggers automatically execute predefined actions in response to specific database events. They help maintain data integrity and reduce repetitive manual logic.

### Types of Triggers

* **BEFORE INSERT / UPDATE / DELETE**: Execute logic before the row is modified.
* **AFTER INSERT / UPDATE / DELETE**: Execute logic after the row has been modified.

### Example: BEFORE INSERT Trigger

```sql
CREATE TRIGGER before_user_insert
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    SET NEW.created_at = NOW();
END;
```

### Example: AFTER UPDATE Trigger

```sql
CREATE TRIGGER after_user_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO user_logs(user_id, changed_at)
    VALUES(NEW.id, NOW());
END;
```

---

## 2. Views

### Overview

A **View** is a virtual table created using a SQL query. It does not store data physically but provides a simplified or secured representation of data.

### Benefits of Views

* Simplify complicated joins and aggregations.
* Provide restricted data access.
* Enhance readability and maintainability.

### Example: Creating a View

```sql
CREATE VIEW active_customers AS
SELECT id, name, email
FROM customers
WHERE status = 'active';
```

### Using the View

```sql
SELECT * FROM active_customers;
```

---

## 3. Stored Functions

### Overview

Stored functions allow you to encapsulate reusable logic and return computed values. They are especially useful for calculations and custom transformations.

### Example: Creating a Function

```sql
DELIMITER $$
CREATE FUNCTION get_discount(price DECIMAL(10,2))
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    RETURN price * 0.10;
END $$
DELIMITER ;
```

### Using the Function

```sql
SELECT product_name, get_discount(price) AS discount
FROM products;
```

---

---

## Best Practices

### Triggers

* Keep trigger logic small and efficient to avoid hidden performance issues.
* Avoid complex business logic inside triggers; prefer stored procedures where possible.
* Always document trigger purpose and affected tables.
* Use BEFORE triggers for data validation, AFTER triggers for logging.

### Views

* Use views to abstract repeated joins or calculations.
* Ensure view names clearly describe the purpose.
* Avoid using overly complex nested views, as they can impact performance.
* Use `WITH CHECK OPTION` to maintain data integrity when updating through views.

### Stored Functions

* Keep functions deterministic whenever possible.
* Do not modify data inside a function (MySQL restricts this to maintain predictability).
* Use functions for reusable logic like transformations or computations.
* Name functions clearly to indicate the action they perform.

---

## Advanced Concepts

### 1. Trigger Use Cases

#### Auditing Changes

Track all updates to a table and store previous values:

```sql
CREATE TRIGGER audit_employee_update
AFTER UPDATE ON employees
FOR EACH ROW
BEGIN
    INSERT INTO employees_audit(emp_id, old_salary, new_salary, changed_at)
    VALUES(OLD.id, OLD.salary, NEW.salary, NOW());
END;
```

#### Enforcing Business Rules

```sql
CREATE TRIGGER before_order_insert
BEFORE INSERT ON orders
FOR EACH ROW
BEGIN
    IF NEW.amount <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Order amount must be greater than zero';
    END IF;
END;
```

### 2. Advanced View Techniques

#### Indexed Views (Materialized-like Behavior)

MySQL does not support materialized views natively, but you can simulate them by creating a table and refreshing it using events or procedures.

#### Updating Through Views

```sql
CREATE VIEW active_users AS
SELECT id, name, status
FROM users
WHERE status = 'active'
WITH CHECK OPTION;
```

This ensures that any UPDATE through the view keeps the row active.

### 3. Stored Functions – More Examples

#### Text Formatting Function

```sql
DELIMITER $$
CREATE FUNCTION capitalize(text VARCHAR(255))
RETURNS VARCHAR(255)
DETERMINISTIC
BEGIN
    RETURN CONCAT(UCASE(LEFT(text, 1)), LCASE(SUBSTRING(text, 2)));
END $$
DELIMITER ;
```

#### Tax Calculation Function

```sql
DELIMITER $$
CREATE FUNCTION calculate_tax(price DECIMAL(10,2), rate DECIMAL(5,2))
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    RETURN price + (price * rate / 100);
END $$
DELIMITER ;
```

---

---

## Real-World Use Cases

### 1. Triggers in Enterprise Applications

* **Automatic Logging:** Maintain detailed audit trails without changing application logic.
* **Soft Deletes:** Enforce soft-delete patterns by moving deleted rows to an archive table.
* **Data Validation:** Ensure invalid or incomplete data never enters the database.
* **Synchronizing Tables:** Keep reporting or summary tables updated in real time.

#### Example: Soft Delete Trigger

```sql
CREATE TRIGGER archive_before_delete
BEFORE DELETE ON orders
FOR EACH ROW
BEGIN
    INSERT INTO orders_archive
    SELECT *, NOW() AS archived_at
    FROM orders
    WHERE id = OLD.id;
END;
```

### 2. Views in Complex Systems

* **Role-based Data Exposure:** Create views for different departments (HR, Finance) showing only allowed columns.
* **Query Simplification:** Abstract multi-join reporting queries.
* **Security Layers:** Prevent direct access to sensitive columns.
* **Versioned Data:** Use views to show the latest version of records.

#### Example: Department-Level View

```sql
CREATE VIEW hr_employees AS
SELECT id, name, designation, salary
FROM employees
WHERE department = 'HR';
```

### 3. Stored Functions in Business Logic

* **Consistent Calculations:** Tax, discounts, interest, aging, weighted scores.
* **Data Transformations:** Clean, normalize, or reformat data before output.
* **Conditional Logic:** Easily integrate complex decisions inside SQL queries.

#### Example: Customer Category Function

```sql
DELIMITER $$
CREATE FUNCTION get_customer_category(total_spent DECIMAL(10,2))
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    IF total_spent > 100000 THEN
        RETURN 'Platinum';
    ELSEIF total_spent > 50000 THEN
        RETURN 'Gold';
    ELSEIF total_spent > 10000 THEN
        RETURN 'Silver';
    ELSE
        RETURN 'Regular';
    END IF;
END $$
DELIMITER ;
```

---

## Trigger Execution Order & Limitations

### Execution Notes

* Only one trigger of each type (BEFORE/AFTER INSERT/UPDATE/DELETE) per table.
* Triggers execute per row (FOR EACH ROW), not per statement.
* Triggers cannot call stored procedures that modify data.
* Circular trigger references can cause failures.

### Performance Tips

* Avoid heavy computations inside triggers.
* Avoid external calls (e.g., HTTP) inside triggers.
* Use indexing on the audit or logging tables.

---

## View Maintenance & Performance Considerations

### Key Concepts

* Views do not store data (unless using materialized patterns).
* Complex views may reduce query performance.
* Use **EXPLAIN** to analyze view execution plans.

### Updatable Views Rules

A view is updatable if:

* It has a single base table.
* It does not contain aggregate functions.
* It does not use DISTINCT, GROUP BY, UNION, or subqueries.

### WITH CHECK OPTION

Prevents inserting rows that don’t satisfy the view condition.

---

## Stored Functions: Advanced Topics

### Deterministic vs Non-Deterministic

* **DETERMINISTIC**: Always returns same output for same input.
* **NON-DETERMINISTIC**: Uses functions like NOW(), RAND(), etc.

### When to Use Stored Functions

* Ideal for computed fields.
* Useful for reusable logic needed in multiple reports.

### When Not to Use Stored Functions

* When the logic requires database writes.
* When the function requires heavy business logic better suited for application layer.

---

## Hands-On Practice Tasks

### Trigger Tasks

1. Create an audit trigger capturing before/after salary changes.
2. Create validation trigger preventing negative stock quantities.
3. Create a trigger that automatically generates a unique invoice code.

### Views Tasks

1. Build a view summarizing monthly sales with joins.
2. Create a security view hiding personal details.
3. Design a view for reporting with computed columns.

### Stored Functions Tasks

1. Write a function to calculate shipping charges dynamically.
2. Create a function to convert text to URL-safe format.
3. Write a function to calculate age from date of birth.

---

## Summary

By mastering:

* **SQL Triggers**,
* **Views**, and
* **Stored Functions**,

you gain strong control over data processing, abstraction, and optimization inside MySQL.

This document provides a structured foundation to help you start practicing these advanced concepts effectively.
