import { Routes } from '@angular/router';
import { BlogsComponent } from './blogs/blogs/blogs.component';
import { LoginComponent } from './auth/login/login.component';
import { authGuard } from './auth/auth.guard';
import { RegisterComponent } from './auth/register/register.component';

export const routes: Routes = [
    {path: '', component: LoginComponent},
    {path: 'register', component: RegisterComponent},
    {path: 'blogs', component: BlogsComponent,canActivate:[authGuard]},
];
