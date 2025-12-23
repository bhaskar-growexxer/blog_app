# Angular SSR Setup Guide
---

## 1. Prerequisites

Ensure the existing project meets the following requirements:

* Angular **v14+**
* Node.js **v18+**
* npm **v9+**
* Application builds successfully in CSR mode

Check versions:

```bash
ng version
node -v
npm -v
```

---

## 2. Existing Project Folder Structure (Before SSR)

Your project should already follow a feature-based structure similar to:

```
src/
 ├── app/
 │   ├── core/
 │   ├── shared/
 │   ├── features/
 │   ├── services/
 │   └── app-routing.module.ts
 ├── assets/
 ├── environments/
```

No restructuring is required to add SSR.

---

## 3. Add Angular Universal (SSR)

Run the following command **inside the existing Angular project**:

```bash
ng add @angular/ssr
```

This will automatically:

* Install required dependencies
* Create `server.ts`
* Create `main.server.ts`
* Create `app.server.module.ts`
* Update `angular.json`

No existing code is removed.

---

## 4. Files Added by SSR

```
src/
 ├── app/
 │   ├── core/            # Singleton services, guards, interceptors
 │   ├── shared/          # Reusable components, pipes, directives
 │   ├── features/        # Feature-based modules
 │   │    ├── auth/
 │   │    ├── dashboard/
 │   │    └── reports/
 │   ├── models/          # Interfaces / models
 │   ├── services/        # API services
 │   └── app-routing.module.ts
 ├── assets/
 ├── environments/
 │   ├── environment.ts
 │   └── environment.prod.ts
```

---

## 4. Files Added by SSR

After installation, the following files are added:

```
server.ts
main.server.ts
app.server.module.ts
```

These files enable Node.js to render Angular pages on the server.

---

## 5. Environment Configuration (SSR Safe)

### environment.ts

```ts
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000/api'
};
```

### environment.prod.ts

```ts
export const environment = {
  production: true,
  apiUrl: 'https://api.yourdomain.com/api'
};
```

Usage:

```ts
import { environment } from 'src/environments/environment';
```

---

## 6. HTTP & API Setup (Important for SSR)

### Import HttpClient

```ts
import { HttpClientModule } from '@angular/common/http';
```

Add in `app.module.ts`:

```ts
imports: [HttpClientModule]
```

### Sample API Service

```ts
@Injectable({ providedIn: 'root' })
export class UserService {

  constructor(private http: HttpClient) {}

  getUsers() {
    return this.http.get(`${environment.apiUrl}/users`);
  }
}
```

---

## 7. Routing Compatibility with SSR

```ts
const routes: Routes = [
  {
    path: 'auth',
    loadChildren: () => import('./features/auth/auth.module').then(m => m.AuthModule)
  },
  {
    path: '',
    loadChildren: () => import('./features/dashboard/dashboard.module').then(m => m.DashboardModule)
  }
];
```

Use **lazy loading** for performance.

---

## 8. Authentication with SSR (JWT Recommended)

* Login API returns JWT
* Store token in memory or cookie
* Use HTTP interceptor

### Auth Interceptor

```ts
intercept(req: HttpRequest<any>, next: HttpHandler) {
  const token = localStorage.getItem('token');

  if (token) {
    req = req.clone({
      setHeaders: { Authorization: `Bearer ${token}` }
    });
  }

  return next.handle(req);
}
```

---

## 9. Guards with SSR

```ts
canActivate(): boolean {
  return !!localStorage.getItem('token');
}
```

Use guards for route protection.

---

## 10. Build & Run SSR

### Development (SSR Mode)

```bash
npm run dev:ssr
```

### Production Build

```bash
npm run build:ssr
```

Output:

```
dist/
 ├── browser/
 └── server/
```

### Start SSR Server

```bash
node dist/server/main.js
```

---

## 11. Nginx Configuration (SSR)

```nginx
location / {
  proxy_pass http://localhost:4000;
  proxy_http_version 1.1;
  proxy_set_header Host $host;
}
```

---

## 12. SSR-Specific Best Practices

* Use **feature-based modules**
* Avoid logic in components
* Use `OnPush` change detection
* Strong typing with interfaces

---

## 13. Common SSR Issues & Fixes

* Angular SSR (SEO)
* PWA support
* State management (NgRx)
* ESLint + Prettier

---

## 14. Using Laravel Backend with SSR

* Laravel as **API-only**
* Enable CORS
* Use token-based auth
* No Blade rendering for Angular

---

## 15. Summary

✔ Scalable structure
✔ API-driven architecture
✔ Production-ready standards
✔ Suitable for ERP & product apps

---

**Author**: Bhasker Reddy
**Use Case**: Enterprise Angular Applications
