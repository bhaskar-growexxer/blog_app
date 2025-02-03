import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';
import { Router } from '@angular/router';

@Injectable({
  providedIn: 'root'
})
export class AuthServiceService {

  private readonly apiUrl = `${environment.api_url}/auth`;

  constructor(private readonly http: HttpClient,private readonly router:Router) {}

  // Handles Login Request
  login(email: string, password: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/login`, { email, password });
  }

  // Handles Register Request
  register(name: string, email: string, password: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/register`, {
      name,
      email,
      password
    });
  }

  //returns the current user
  getCurrentUser(): any {
    return JSON.parse(localStorage.getItem('user') ?? '{}');
  }

  //Handles Logout Request
  logout(): any {
    
    return this.http.post(`${this.apiUrl}/logout`, {}).subscribe({
      next: (response) => {
        localStorage.removeItem('user');
        localStorage.removeItem('token');
        this.router.navigate(['/']);
      },
      error: (error) => {
        console.error('Failed to logout', error);
      }
    });
  }
}
