import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, of, combineLatest } from 'rxjs';
import { debounceTime, distinctUntilChanged, switchMap, map, shareReplay, startWith } from 'rxjs/operators';
import { environment } from '../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class BlogServiceService {

  private readonly apiUrl = `${environment.api_url}/blogs`;

  public readonly blogCategories : string[] = ['Technology', 'Health', 'Science', 'Business', 'Entertainment', 'Sports', 'Education', 'Lifestyle', 'Politics', 'Travel'];

  constructor(private readonly http: HttpClient) {}

  // Get all blogs or with optional filters (search, category).
  // Uses debounce + distinctUntilChanged + switchMap internally.
  getBlogs(filters?: { search?: string; category?: string }): Observable<any> {
    return of(filters ?? {}).pipe(
      debounceTime(200),
      distinctUntilChanged((a, b) => (a?.search ?? '') === (b?.search ?? '') && (a?.category ?? '') === (b?.category ?? '')),
      switchMap(f => {
        const params: any = {};
        if (f?.search) params.search = f.search;
        if (f?.category) params.category = f.category;
        return this.http.get(`${this.apiUrl}`, { params });
      }),
      // keep behaviour stable for multiple subscribers
      shareReplay({ bufferSize: 1, refCount: true })
    );
  }

  // Convenience: take streams for search/category/refresh and return combined stream of server results.
  watchBlogs(search$: Observable<string>, category$: Observable<string>, refresh$: Observable<void>): Observable<any> {
    return combineLatest([
      refresh$.pipe(startWith<void, void>(undefined)),
      search$.pipe(startWith(''), debounceTime(300), distinctUntilChanged()),
      category$.pipe(startWith(''))
    ]).pipe(
      switchMap(([_, search, category]) => this.getBlogs({ search, category }))
    );
  }

  // Get a single blog by ID
  getBlogById(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/${id}`);
  }

  // Create a new blog
  createBlog(blogData: { title: string; description: string }): Observable<any> {
    return this.http.post(`${this.apiUrl}`, blogData);
  }

  // Update a blog by ID
  updateBlog(blogData: { id: number; title: string; description: string }): Observable<any> {
    return this.http.put(`${this.apiUrl}/${blogData.id}`, blogData);
  }

  // Delete a blog by ID
  deleteBlog(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }
}
