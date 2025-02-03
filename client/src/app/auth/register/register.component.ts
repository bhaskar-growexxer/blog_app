import { Component } from '@angular/core';
import { AuthServiceService } from '../../../services/auth-service.service';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule,CommonModule,RouterModule],
  templateUrl: './register.component.html',
  styleUrl: './register.component.css'
})
export class RegisterComponent {

  registerForm: FormGroup;
  errorMsg: string = '';

  constructor(private readonly authService: AuthServiceService,formBuilder: FormBuilder,private readonly router: Router) {
    this.registerForm = formBuilder.group({
      name: ['',[Validators.required,Validators.minLength(3)]],
      email: ['',[Validators.required,Validators.email]],
      password: ['',[Validators.required,Validators.minLength(3)]]
    });
  }

  register() {
    if(this.registerForm.invalid) {
      return;
    }
    const {email,password,name} = this.registerForm.value;


    this.authService.register(name,email,password).subscribe({
      next: (response) => {
        if(response.isSuccess) {
          localStorage.setItem('user', JSON.stringify(response.user));
          localStorage.setItem('token', response.token);
          this.router.navigate(['/blogs']);
        }
        this.errorMsg = response.message;
        console.log('Login successful',response);
      },
      error: (response) => {
        this.errorMsg = response.error.message;
      }
    });
  }

}
