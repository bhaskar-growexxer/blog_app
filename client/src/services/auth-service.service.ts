/** Base API response wrapper using Generics */
export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

/** Login request payload */
export interface LoginRequest {
  email: string;
  password: string;
}

/** Register request payload */
export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
}

/** User domain model */
export interface User {
  id: string;
  name: string;
  email: string;
}

/** Auth response payload */
export interface AuthPayload {
  token: string;
  user: User;
}

// ===============================
// Auth Service (Modern TypeScript)
// ===============================

import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, tap } from 'rxjs';
import { environment } from '../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class AuthServiceService {

  // Dependency Injection using `inject()` (Angular 14+)
  private readonly http = inject(HttpClient);
  private readonly router = inject(Router);

  private readonly apiUrl = `${environment.api_url}/auth` as const;

  // -------------------------------
  // Login
  // -------------------------------
  login(payload: LoginRequest): Observable<ApiResponse<AuthPayload>> {
    return this.http
      .post<ApiResponse<AuthPayload>>(`${this.apiUrl}/login`, payload)
      .pipe(
        tap(({ data }) => {
          this.persistSession(data);
        })
      );
  }

  // -------------------------------
  // Register
  // -------------------------------
  register(payload: RegisterRequest): Observable<ApiResponse<AuthPayload>> {
    return this.http
      .post<ApiResponse<AuthPayload>>(`${this.apiUrl}/register`, payload)
      .pipe(
        tap(({ data }) => {
          this.persistSession(data);
        })
      );
  }

  // -------------------------------
  // Current User
  // -------------------------------
  getCurrentUser(): User | null {
    const raw = localStorage.getItem('user');
    return raw ? (JSON.parse(raw) as User) : null;
  }

  // -------------------------------
  // Logout
  // -------------------------------
  logout(): Observable<ApiResponse<null>> {
    return this.http
      .post<ApiResponse<null>>(`${this.apiUrl}/logout`, {})
      .pipe(
        tap(() => {
          this.clearSession();
          this.router.navigate(['/']);
        })
      );
  }

  // ===============================
  // Private Helpers
  // ===============================

  /** Persist auth session safely */
  private persistSession(payload: AuthPayload): void {
    localStorage.setItem('token', payload.token);
    localStorage.setItem('user', JSON.stringify(payload.user));
  }

  /** Clear auth session */
  private clearSession(): void {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  }
}

// ===============================
// Usage Example (Component)
// ===============================
/*
this.authService
  .login({ email, password })
  .subscribe({
    next: (res) => console.log(res.data.user),
    error: (err) => console.error(err)
  });
*/
