import { Component,inject,OnInit } from '@angular/core';
import { BlogServiceService } from '../../../services/blog-service.service';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { NewBlogComponent } from '../new-blog/new-blog.component';
import { MatDialog } from '@angular/material/dialog'; 
import { AuthServiceService } from '../../../services/auth-service.service';
import { EditBlogComponent } from '../edit-blog/edit-blog.component';
import {Blog} from '../../models/blog.model';
import {BlogCategory} from '../../models/blog.model';
import {User} from '../../models/user.model';
import { signal, computed } from '@angular/core';
@Component({
  selector: 'app-blogs',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './blogs.component.html',
  styleUrl: './blogs.component.css'
})
export class BlogsComponent implements OnInit {

  // -------------------------------
  // Dependencies (modern inject)
  // -------------------------------
  private readonly blogService = inject(BlogServiceService);
  private readonly authService = inject(AuthServiceService);
  private readonly dialog = inject(MatDialog);
  private readonly router = inject(Router);

  // -------------------------------
  // State (Signals)
  // -------------------------------

  readonly blogs = signal<Blog[]>([]);
  readonly searchQuery = signal('');
  readonly selectedCategory = signal<BlogCategory | ''>('');
  readonly displaySelfBlogs = signal(false);

  readonly user = signal<User | null>(null);

  readonly blogCategories = this.blogService.blogCategories;

  // -------------------------------
  // Derived state
  // -------------------------------

  readonly filteredBlogs = computed(() => {
    const query = this.searchQuery().toLowerCase();
    const category = this.selectedCategory();
    const onlyMine = this.displaySelfBlogs();
    const email = this.user()?.email;

    return this.blogs().filter(blog => {
      const matchesSearch =
        blog.title.toLowerCase().includes(query) ||
        blog.description.toLowerCase().includes(query) ||
        blog.author.toLowerCase().includes(query);

      const matchesCategory = category ? blog.category === category : true;
      const matchesOwner = onlyMine ? blog.author === email : true;

      return matchesSearch && matchesCategory && matchesOwner;
    });
  });

  // -------------------------------
  // Lifecycle
  // -------------------------------

  ngOnInit(): void {
    this.user.set(this.authService.getCurrentUser());
    this.fetchBlogs();
  }

  // -------------------------------
  // Data
  // -------------------------------

  private fetchBlogs(): void {
    this.blogService.getBlogs().subscribe({
      next: (res) => this.blogs.set([...res.data].reverse()),
      error: (err) => console.error('Failed to fetch blogs', err)
    });
  }

  // -------------------------------
  // UI Actions
  // -------------------------------

  resetFilters(): void {
    this.searchQuery.set('');
    this.selectedCategory.set('');
    this.displaySelfBlogs.set(false);
  }

  openNewBlogModal(): void {
    this.dialog
      .open(NewBlogComponent, { width: '500px', height: '500px' })
      .afterClosed()
      .subscribe((blog: Blog | null) => {
        if (!blog) return;
        this.blogs.update(b => [blog, ...b]);
      });
  }

  openEditBlogModal(blog: Blog): void {
    this.dialog
      .open(EditBlogComponent, {
        width: '500px',
        height: '500px',
        data: blog
      })
      .afterClosed()
      .subscribe((updated: Blog | null) => {
        if (!updated) return;
        this.blogs.update(list =>
          list.map(b => (b.id === updated.id ? updated : b))
        );
      });
  }

  deleteBlog(id: Blog['id']): void {
    this.blogService.deleteBlog(id).subscribe({
      next: () => {
        this.blogs.update(list => list.filter(b => b.id !== id));
      },
      error: err => console.error('Delete failed', err)
    });
  }

  showMyBlogs(): void {
    this.resetFilters();
    this.displaySelfBlogs.set(true);
  }

  logout(): void {
    this.authService.logout().subscribe();
  }
}
