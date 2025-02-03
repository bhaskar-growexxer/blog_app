import { Component } from '@angular/core';
import { AuthServiceService } from '../../../services/auth-service.service';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule,CommonModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {

  loginForm: FormGroup;
  errorMsg: string = '';

  constructor(private readonly authService: AuthServiceService,formBuilder: FormBuilder,private readonly router: Router) {
    this.loginForm = formBuilder.group({
      email: ['',[Validators.required,Validators.email]],
      password: ['',[Validators.required,Validators.minLength(3)]]
    });
  }

  login() {
    if(this.loginForm.invalid) {
      return;
    }
    const {email,password} = this.loginForm.value;


    this.authService.login(email,password).subscribe({
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
