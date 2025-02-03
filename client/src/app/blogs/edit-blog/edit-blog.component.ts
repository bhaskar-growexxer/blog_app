import { Component, Inject } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { BlogServiceService } from '../../../services/blog-service.service';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { AuthServiceService } from '../../../services/auth-service.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-edit-blog',
  imports: [ReactiveFormsModule, CommonModule],
  templateUrl: './edit-blog.component.html',
  styleUrl: './edit-blog.component.css'
})
export class EditBlogComponent {

  blogForm: FormGroup;
  user: any = {};
  
  constructor(
    private readonly fb: FormBuilder,
    private readonly blogService: BlogServiceService,
    private readonly dialogRef: MatDialogRef<EditBlogComponent>,
    private readonly authService: AuthServiceService,
    @Inject(MAT_DIALOG_DATA) public data: any
  ) {

    this.user = this.authService.getCurrentUser();
    this.blogForm = this.fb.group({
      title: [data.title, Validators.required],
      category: [data.category, Validators.required],
      description: [data.description, [Validators.required, Validators.minLength(10)]]
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

    let blog = this.blogForm.value;
    blog.author = this.user.email;
    blog.id = this.data.id;
    
    this.blogService.updateBlog(blog).subscribe({
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
