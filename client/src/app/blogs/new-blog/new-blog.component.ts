import { Component } from '@angular/core';
import { FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { BlogServiceService } from '../../../services/blog-service.service';
import { CommonModule } from '@angular/common';
import { MatDialogRef } from '@angular/material/dialog';
import { AuthServiceService } from '../../../services/auth-service.service';

@Component({
  selector: 'app-new-blog',
  imports: [FormsModule,CommonModule,ReactiveFormsModule],
  templateUrl: './new-blog.component.html',
  styleUrl: './new-blog.component.css'
})
export class NewBlogComponent {

  blogForm: FormGroup;
  user: any = {};
  
  constructor(
    private readonly fb: FormBuilder,
    private readonly blogService: BlogServiceService,
    private readonly dialogRef: MatDialogRef<NewBlogComponent>,
    private readonly authService: AuthServiceService
  ) {

    this.user = this.authService.getCurrentUser();
    this.blogForm = this.fb.group({
      title: ['', Validators.required],
      category: ['', Validators.required],
      description: ['', [Validators.required, Validators.minLength(10)]]
    });
  }

  // Get categories from the service
  categories() {
    return this.blogService.blogCategories;
  }

  onSubmit() {
    if (this.blogForm.invalid) {
      return;
    }

    let newBlog = this.blogForm.value;
    newBlog.author = this.user.email;
    
    this.blogService.createBlog(newBlog).subscribe({
      next: (response) => {
        console.log('New blog created:', response);
        this.blogForm.reset();
        this.dialogRef.close(response.data);
      },
      error: (error) => {
        console.error('Error creating blog:', error);
      }
    });
  }

}
