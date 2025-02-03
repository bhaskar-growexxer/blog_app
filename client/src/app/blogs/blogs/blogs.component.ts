import { Component } from '@angular/core';
import { BlogServiceService } from '../../../services/blog-service.service';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { NewBlogComponent } from '../new-blog/new-blog.component';
import { MatDialog } from '@angular/material/dialog'; 
import { AuthServiceService } from '../../../services/auth-service.service';

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

  constructor(private readonly blogService: BlogServiceService,private readonly dialog: MatDialog,private readonly authService: AuthServiceService,private readonly router: Router) {
  }

  ngOnInit() {
    // Fetch all blogs when the component initializes
    this.user = this.authService.getCurrentUser();
    this.fetchBlogs();
    this.blogCategories = this.blogService.blogCategories;
  }

  // Fetch all blogs from the BlogService
  fetchBlogs() {
    this.blogService.getBlogs().subscribe({
      next: (response) => {
        console.log('Fetched blogs', response);
        this.blogs = response.data.reverse(); // Saving the original list
        this.filteredBlogs = [...this.blogs];
      },
      error: (error) => {
        console.error('Failed to fetch blogs', error);
      }
    });
  }

  filterBlogs() {
    this.filteredBlogs = this.blogs.filter((blog) => {
      let matchesSearch = blog.title.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                            blog.description.toLowerCase().includes(this.searchQuery.toLowerCase());
      if (this.displaySelfBlogs) {
        matchesSearch = matchesSearch && blog.author === this.user.email;
      }
      const matchesCategory = this.selectedCategory ? blog.category === this.selectedCategory : true;
      return matchesSearch && matchesCategory;
    });
  }

  resetFilters() {
    this.searchQuery = '';
    this.selectedCategory = '';
    this.filteredBlogs = [...this.blogs];
  }

  openNewBlogModal() {
    const dialogRef =this.dialog.open(NewBlogComponent,{width:'500px',height:'500px'});

    dialogRef.afterClosed().subscribe(blog => {
      if (blog) {

        this.blogs.push(blog);

        let matchesSearch = blog.title.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                            blog.description.toLowerCase().includes(this.searchQuery.toLowerCase());
        if (this.displaySelfBlogs) {
          matchesSearch = matchesSearch && blog.author === this.user.email;
        }

        if(matchesSearch && (!this.selectedCategory || blog.category === this.selectedCategory)){
          this.filteredBlogs.unshift(blog);
        }

        console.log('New blog added:', blog);
      }
    });
  }

  displaySelf() {
    this.resetFilters();
    this.displaySelfBlogs = true;
    this.filteredBlogs = this.filteredBlogs.filter((blog) => {
      return blog.author == this.user.email;
    }); 
    this.user.totalBlogs = this.filteredBlogs.length;
  }

  logout() {
    this.authService.logout();
  }

}
