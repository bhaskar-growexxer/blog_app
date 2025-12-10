# TypeScript Concepts — Strong Typing, Interfaces, Types, Unions & Generics
---

## Contents

1. Strong Typing (Primitives & Objects)
2. Interfaces
3. Type Aliases
4. Union Types & Type Narrowing
5. Generics
6. Putting it together — small examples & exercises
7. Quick CLI: how to run TypeScript snippets
8. Best practices & common pitfalls

---

# 1. Strong Typing (Primitives & Objects)

**What it is:** TypeScript is a statically typed superset of JavaScript. Strong typing means variables, function parameters and returns can (and should) have explicit types. This helps catch errors at compile time.

### Example — primitives

```ts
// primitives with explicit types
let name: string = "Bhasker";
let age: number = 32;
let isActive: boolean = true;

// inferred typing
let city = "Bengaluru"; // inferred as string

// arrays
let ids: number[] = [1, 2, 3];
let names: Array<string> = ["a", "b"]; // generic array syntax

// any (avoid when possible)
let something: any = 5;
something = "now a string"; // allowed but loses safety

// unknown (safer than any)
let maybe: unknown = 10;

// tuple
let pair: [number, string] = [1, "one"];
```

### Example — objects and type inference

```ts
// inline object type
const user: { id: number; name: string; active?: boolean } = {
  id: 1,
  name: "Asha",
};

// accessing wrong property will be a compile-time error
// console.log(user.age); // Error: Property 'age' does not exist
```

**Tip:** Prefer explicit types on public APIs (function signatures, exports) and allow inference for local variables when it's clear.

---

# 2. Interfaces

**What it is:** An `interface` describes the shape of an object — which properties and methods it must have. Interfaces are extendable and are primarily used for object types and class contracts.

### Basic interface

```ts
interface User {
  id: number;
  name: string;
  email?: string; // optional
}

function greet(u: User) {
  return `Hello ${u.name}`;
}

const u: User = { id: 10, name: "Rani" };
console.log(greet(u));
```

### Extending interfaces

```ts
interface WithTimestamps {
  createdAt: Date;
  updatedAt?: Date;
}

interface Product extends WithTimestamps {
  id: string;
  title: string;
}

const p: Product = { id: "p1", title: "Pen", createdAt: new Date() };
```

### Implementing interfaces in classes

```ts
interface Logger {
  log(message: string): void;
}

class ConsoleLogger implements Logger {
  log(message: string) {
    console.log("LOG:", message);
  }
}
```

**When to use:** Use interfaces for object shapes you expect to extend, implement, or use across modules.

---

# 3. Type Aliases

**What it is:** `type` creates an alias for any type — primitives, unions, tuples, functions, etc. It's more flexible than `interface` in some cases (e.g., union types).

### Examples

```ts
// alias for an object
type Point = { x: number; y: number };

// alias for a union
type ID = string | number;

// function type alias
type Mapper = (s: string) => string;

const fn: Mapper = (s) => s.toUpperCase();
```

**Interface vs Type:** Many cases are interchangeable. Use `interface` when you expect object shapes that might be extended or implemented. Use `type` for unions, tuples and complex combinations.

---

# 4. Union Types & Type Narrowing

**What it is:** Union types let a value be one of several types. Narrowing is the process of checking the type at runtime to let TypeScript know which branch you're in.

### Union examples

```ts
function printId(id: string | number) {
  // type narrowing
  if (typeof id === "string") {
    console.log(id.toUpperCase());
  } else {
    console.log(id.toFixed(0));
  }
}

printId("abc");
printId(123);
```

### Discriminated unions (recommended for complex variants)

```ts
interface Square {
  kind: "square";
  size: number;
}

interface Circle {
  kind: "circle";
  radius: number;
}

type Shape = Square | Circle;

function area(s: Shape) {
  switch (s.kind) {
    case "square":
      return s.size * s.size;
    case "circle":
      return Math.PI * s.radius ** 2;
  }
}
```

### Type guards & user-defined guards

```ts
function isString(x: unknown): x is string {
  return typeof x === "string";
}

function process(x: string | number | undefined) {
  if (isString(x)) {
    // now x is string
    console.log(x.toUpperCase());
  }
}
```

**Tip:** Prefer discriminated unions for predictable runtime checks.

---

# 5. Generics

**What it is:** Generics let you write reusable components that work with a variety of types while preserving type information.

### Generic function

```ts
function identity<T>(value: T): T {
  return value;
}

const num = identity<number>(123);
const str = identity("hello"); // compiler infers T = string
```

### Generic interfaces / types

```ts
interface ApiResponse<T> {
  data: T;
  status: number;
}

const res: ApiResponse<{ id: number; name: string }> = {
  data: { id: 1, name: "X" },
  status: 200,
};
```

### Generic constraints

```ts
function mergeObjects<T extends object, U extends object>(a: T, b: U) {
  return { ...a, ...b } as T & U;
}

const merged = mergeObjects({ id: 1 }, { name: "Z" });
// merged has type { id: number } & { name: string }
```

### Generic classes

```ts
class Queue<T> {
  private items: T[] = [];

  enqueue(item: T) {
    this.items.push(item);
  }

  dequeue(): T | undefined {
    return this.items.shift();
  }
}

const q = new Queue<number>();
q.enqueue(10);
```

**Tip:** Use generics to keep APIs flexible and strongly-typed. Add constraints (`extends`) when you need certain properties.

---

# 6. Putting it together — small examples & exercises

### Example: Typed repository pattern

```ts
// Define entity interface
interface Entity { id: string }

// Generic repository
interface Repository<T extends Entity> {
  getById(id: string): T | null;
  save(entity: T): void;
}

class InMemoryRepo<T extends Entity> implements Repository<T> {
  private items = new Map<string, T>();

  getById(id: string): T | null {
    return this.items.get(id) ?? null;
  }

  save(entity: T) {
    this.items.set(entity.id, entity);
  }
}

// Usage
interface User extends Entity { name: string }
const userRepo = new InMemoryRepo<User>();
userRepo.save({ id: 'u1', name: 'Sam' });
console.log(userRepo.getById('u1')?.name);
```

### Exercise ideas (try them):

1. Create a `Result<T, E>` discriminated union type for successful/failure responses and implement a `map` helper.
2. Implement a `SafeParse<T>` helper that narrows `unknown` to `T` using runtime checks.
3. Build a small typed React hook `useFetch<T>(url: string)` that returns `{ data?: T; loading: boolean; error?: Error }`.

---

# 7. Quick CLI: how to run TypeScript snippets

1. Install TypeScript: `npm install -D typescript`
2. Init: `npx tsc --init`
3. Compile: `npx tsc path/to/file.ts`
4. For quick experimentation, you can use `ts-node` (install: `npm i -D ts-node`) and run `npx ts-node file.ts`.

---

# 8. Best practices & common pitfalls

* Prefer explicit types on public API boundaries (exports, function params/returns) and rely on inference internally.
* Use `unknown` instead of `any` for inputs you must validate.
* Prefer discriminated unions for complex variant data.
* Use generics for reusable utilities; add constraints to keep type-safety.
* Avoid excessive `as` casts — they bypass type checks.
* Keep interfaces small and focused; favor composition over inheritance.

---

# References & Further Reading

* TypeScript handbook (official)
* Practical TypeScript articles (search for: discriminated unions, mapped types, conditional types)

---