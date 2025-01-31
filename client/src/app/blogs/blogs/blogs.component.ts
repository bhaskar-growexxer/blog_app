import { Component } from '@angular/core';
import { BlogServiceService } from '../../../services/blog-service.service';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-blogs',
  standalone: true,
  imports : [FormsModule,CommonModule],
  templateUrl: './blogs.component.html',
  styleUrl: './blogs.component.css'
})
export class BlogsComponent {

  blogs: any[] = []; // Original list of blogs
  filteredBlogs: any[] = []; // Filtered list of blogs
  searchQuery: string = ''; // Search input
  selectedCategory: string = ''; // Selected category for filtering

  constructor(private readonly blogService: BlogServiceService) {
  }

  ngOnInit() {
    // Fetch all blogs when the component initializes
    this.fetchBlogs();
  }

  // Fetch all blogs from the BlogService
  fetchBlogs() {
    this.blogService.getBlogs().subscribe({
      next: (response) => {
        console.log('Fetched blogs', response);
        this.blogs = response.data; // Save the original list
        this.filteredBlogs = [...this.blogs]; // Initialize filtered list with all blogs
      },
      error: (error) => {
        console.error('Failed to fetch blogs', error);
      }
    });
  }

  // Filter blogs based on search query and category
  filterBlogs() {
    this.filteredBlogs = this.blogs.filter((blog) => {
      const matchesSearch = blog.title.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                            blog.description.toLowerCase().includes(this.searchQuery.toLowerCase());
      const matchesCategory = this.selectedCategory ? blog.category === this.selectedCategory : true;
      return matchesSearch && matchesCategory;
    });
  }

  // Reset filters and show all blogs
  resetFilters() {
    this.searchQuery = '';
    this.selectedCategory = '';
    this.filteredBlogs = [...this.blogs];
  }
}
// when i open localhost:4200/blogs its going to localhost:4200
