# Advanced RxJS Operators & Reactive Programming

This document covers commonly used advanced RxJS operators with clear explanations and practical examples. The focus is on **real-world async flow management** and **Angular-style demo use cases**.

---

## 1. switchMap

### What it does

* Cancels the previous inner observable when a new value is emitted
* Best for **search**, **typeahead**, **API calls based on latest input**

### Example: Search API (Latest request only)

```ts
searchControl.valueChanges.pipe(
  debounceTime(300),
  switchMap(term => this.searchService.search(term))
).subscribe(results => {
  this.results = results;
});
```

### Key points

* Prevents race conditions
* Automatically unsubscribes from previous API calls

---

## 2. mergeMap

### What it does

* Executes multiple inner observables **in parallel**
* Does NOT cancel previous observables

### Example: Upload multiple files

```ts
from(files).pipe(
  mergeMap(file => this.uploadService.upload(file))
).subscribe(response => {
  console.log('File uploaded', response);
});
```

### When to use

* Independent async operations
* Parallel processing (e.g., batch API calls)

---

## 3. debounceTime

### What it does

* Emits value only after a specified time of inactivity
* Reduces unnecessary API calls

### Example: Prevent frequent API hits

```ts
inputControl.valueChanges.pipe(
  debounceTime(500)
).subscribe(value => {
  console.log('User stopped typing:', value);
});
```

### Common use cases

* Search boxes
* Form validations
* User input optimizations

---

## 4. combineLatest

### What it does

* Combines latest values from multiple observables
* Emits when **any observable emits** (after all have emitted once)

### Example: Filter products by category & price

```ts
combineLatest([
  this.category$,
  this.priceRange$
]).pipe(
  switchMap(([category, price]) =>
    this.productService.getProducts(category, price)
  )
).subscribe(products => {
  this.products = products;
});
```

### Key points

* All observables must emit at least once
* Great for dynamic filters

---

## 5. Managing Async Flows Using RxJS Patterns

### Pattern 1: Chained API Calls

```ts
this.authService.login(credentials).pipe(
  switchMap(user => this.profileService.getProfile(user.id))
).subscribe(profile => {
  this.profile = profile;
});
```

### Pattern 2: Conditional Async Flow

```ts
this.user$.pipe(
  mergeMap(user => {
    return user.isAdmin
      ? this.adminService.getDashboard()
      : this.userService.getDashboard();
  })
).subscribe(data => {
  this.dashboard = data;
});
```

### Pattern 3: Parallel Requests & Aggregation

```ts
forkJoin({
  user: this.userService.getUser(),
  orders: this.orderService.getOrders()
}).subscribe(({ user, orders }) => {
  this.user = user;
  this.orders = orders;
});
```

---

## 6. Demo Application: RxJS Operators in Action

### Scenario: Product Search Page

Features:

* Search input with debounce
* API call cancellation
* Category & price filters

### Component Logic

```ts
search$ = this.searchControl.valueChanges.pipe(debounceTime(300));
category$ = this.categoryControl.valueChanges;
price$ = this.priceControl.valueChanges;

products$ = combineLatest([
  search$, category$, price$
]).pipe(
  switchMap(([search, category, price]) =>
    this.productService.searchProducts({ search, category, price })
  )
);
```

### Template Usage

```html
<div *ngFor="let product of products$ | async">
  {{ product.name }}
</div>
```

---

## Summary

* `switchMap` → latest async operation only
* `mergeMap` → parallel async operations
* `debounceTime` → optimize frequent events
* `combineLatest` → reactive data combinations

These operators form the foundation of **scalable, reactive Angular applications**.
