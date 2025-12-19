import { Component } from '@angular/core';
import { BlogServiceService } from '../../../services/blog-service.service';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { NewBlogComponent } from '../new-blog/new-blog.component';
import { MatDialog } from '@angular/material/dialog'; 
import { AuthServiceService } from '../../../services/auth-service.service';
import { EditBlogComponent } from '../edit-blog/edit-blog.component';
import { Subject, BehaviorSubject, combineLatest, of } from 'rxjs';
import { debounceTime, distinctUntilChanged, switchMap, map, startWith, tap, mergeMap, takeUntil, filter } from 'rxjs/operators';

@Component({
  selector: 'app-blogs',
  standalone: true,
  imports : [FormsModule,CommonModule,RouterModule],
  templateUrl: './blogs.component.html',
  styleUrl: './blogs.component.css'
})
export class BlogsComponent {

  blogs: any[] = []; // Original list of blogs
  filteredBlogs: any[] = []; // Filtered list of blogs
  searchQuery: string = ''; // Search input
  selectedCategory: string = ''; // Selected category for filtering
  blogCategories: string[] = []; // blog categories
  displaySelfBlogs: boolean = false; // Display only self blogs
  user : any = {};

  // RxJS subjects/streams
  private search$ = new Subject<string>();
  private category$ = new BehaviorSubject<string>('');
  private displaySelf$ = new BehaviorSubject<boolean>(false);
  private refresh$ = new Subject<void>();
  private destroy$ = new Subject<void>();

  constructor(private readonly blogService: BlogServiceService,private readonly dialog: MatDialog,private readonly authService: AuthServiceService,private readonly router: Router) {
  }

  ngOnInit() {
    // Fetch user and categories
    this.user = this.authService.getCurrentUser();
    this.blogCategories = this.blogService.blogCategories;

    // combine latest search/category/display/refresh and fetch using switchMap
    combineLatest([
      this.refresh$.pipe(startWith<void, void>(undefined)),
      this.search$.pipe(startWith(''), debounceTime(300), distinctUntilChanged()),
      this.category$.pipe(startWith('')),
      this.displaySelf$.pipe(startWith(false))
    ]).pipe(
      // when any of those change, fetch blogs (switchMap cancels previous request)
      switchMap(([_, search, category, displaySelf]) =>
        this.blogService.getBlogs({ search, category }).pipe(
          map(response => {
            let blogs = (response?.data ?? []).slice().reverse();
            if (displaySelf && this.user?.email) {
              blogs = blogs.filter((b: any) => b.author === this.user.email);
            }
            return blogs;
          })
        )
      ),
      takeUntil(this.destroy$)
    ).subscribe({
      next: (blogs) => {
        this.blogs = blogs;
        this.filteredBlogs = [...blogs];
        // keep total on display self if applicable
        if (this.displaySelfBlogs) {
          this.user.totalBlogs = this.filteredBlogs.length;
        }
      },
      error: (err) => console.error('Failed to fetch blogs', err)
    });

    // trigger initial load
    this.refresh$.next();
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  // Called from template on input change; debounced before triggering fetch
  filterBlogs() {
    this.search$.next(this.searchQuery ?? '');
    this.category$.next(this.selectedCategory ?? '');
  }

  resetFilters() {
    this.searchQuery = '';
    this.selectedCategory = '';
    this.filteredBlogs = [...this.blogs];
    this.search$.next('');
    this.category$.next('');
    this.displaySelf$ .next(false);
    this.displaySelfBlogs = false;
  }

  openNewBlogModal() {
    const dialogRef = this.dialog.open(NewBlogComponent,{width:'500px',height:'500px'});

    // use mergeMap to process the dialog result (could be used to call API) and then refresh
    dialogRef.afterClosed().pipe(
      filter(blog => !!blog),
      mergeMap(blog => of(blog).pipe(
        tap(() => this.refresh$.next())
      ))
    ).subscribe({
      next: (blog) => {
        console.log('New blog added (dialog result):', blog);
      },
      error: (err) => console.error(err)
    });
  }

  openEditBlogModal(blog: any) {
    const dialogRef = this.dialog.open(EditBlogComponent,{width:'500px',height:'500px',data: blog});

    dialogRef.afterClosed().pipe(
      filter(b => !!b),
      mergeMap(b => of(b).pipe(
        tap(() => this.refresh$.next())
      ))
    ).subscribe({
      next: (updated) => {
        console.log('Blog edited (dialog result):', updated);
      },
      error: (err) => console.error(err)
    });
  }

  deleteBlog(id: number) {
    this.blogService.deleteBlog(id).pipe(
      tap((response) => {
        if (response?.isSuccess) {
          // refresh to get canonical state from server
          this.refresh$.next();
        }
      }),
      takeUntil(this.destroy$)
    ).subscribe({
      next: (resp) => console.log('Blog deleted:', resp),
      error: (err) => console.error('Error deleting blog:', err)
    });
  }

  displaySelf() {
    this.resetFilters();
    this.displaySelfBlogs = true;
    this.displaySelf$.next(true);
    // total will be updated by the combineLatest subscription
  }

  logout() {
    this.authService.logout();
  }

}
