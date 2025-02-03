import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class BlogServiceService {

  private readonly apiUrl = `${environment.api_url}/blogs`;

  public readonly blogCategories : string[] = ['Technology', 'Health', 'Science', 'Business', 'Entertainment', 'Sports', 'Education', 'Lifestyle', 'Politics', 'Travel'];

  constructor(private readonly http: HttpClient) {}

  // Get all blogs
  getBlogs(): Observable<any> {
    return this.http.get(`${this.apiUrl}`);
  }

  // Get a single blog by ID
  getBlogById(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/${id}`);
  }

  // Create a new blog
  createBlog(blogData: { title: string; content: string }): Observable<any> {
    return this.http.post(`${this.apiUrl}`, blogData);
  }

  // Update a blog by ID
  updateBlog(id: number, blogData: { title: string; content: string }): Observable<any> {
    return this.http.put(`${this.apiUrl}/${id}`, blogData);
  }

  // Delete a blog by ID
  deleteBlog(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }
}
