/** Generic API response wrapper */
export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

/** Blog category type (string literal union) */
export type BlogCategory =
  | 'Technology'
  | 'Health'
  | 'Science'
  | 'Business'
  | 'Entertainment'
  | 'Sports'
  | 'Education'
  | 'Lifestyle'
  | 'Politics'
  | 'Travel';

/** Blog domain model */
export interface Blog {
  id: number;
  title: string;
  description: string;
  category: BlogCategory;
  createdAt: string;
  updatedAt?: string;
}

/** Create blog request DTO */
export interface CreateBlogRequest {
  title: string;
  description: string;
  category: BlogCategory;
}

/** Update blog request DTO */
export interface UpdateBlogRequest extends CreateBlogRequest {
  id: number;
}

// ===============================
// Blog Service (Modern TypeScript)
// ===============================

import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class BlogServiceService {

  // Modern Dependency Injection (Angular 14+)
  private readonly http = inject(HttpClient);

  private readonly apiUrl = `${environment.api_url}/blogs` as const;

  // Typed & immutable categories
  public readonly blogCategories: readonly BlogCategory[] = [
    'Technology',
    'Health',
    'Science',
    'Business',
    'Entertainment',
    'Sports',
    'Education',
    'Lifestyle',
    'Politics',
    'Travel'
  ] as const;

  // -------------------------------
  // Get all blogs
  // -------------------------------
  getBlogs(): Observable<ApiResponse<Blog[]>> {
    return this.http.get<ApiResponse<Blog[]>>(this.apiUrl);
  }

  // -------------------------------
  // Get blog by ID
  // -------------------------------
  getBlogById(id: Blog['id']): Observable<ApiResponse<Blog>> {
    return this.http.get<ApiResponse<Blog>>(`${this.apiUrl}/${id}`);
  }

  // -------------------------------
  // Create blog
  // -------------------------------
  createBlog(payload: CreateBlogRequest): Observable<ApiResponse<Blog>> {
    return this.http.post<ApiResponse<Blog>>(this.apiUrl, payload);
  }

  // -------------------------------
  // Update blog
  // -------------------------------
  updateBlog(payload: UpdateBlogRequest): Observable<ApiResponse<Blog>> {
    const { id, ...body } = payload;
    return this.http.put<ApiResponse<Blog>>(`${this.apiUrl}/${id}`, body);
  }

  // -------------------------------
  // Delete blog
  // -------------------------------
  deleteBlog(id: Blog['id']): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.apiUrl}/${id}`);
  }
}
