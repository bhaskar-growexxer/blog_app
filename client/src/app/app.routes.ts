import { Routes } from '@angular/router';
import { BlogsComponent } from './blogs/blogs/blogs.component';
import { LoginComponent } from './auth/login/login.component';

export const routes: Routes = [
    {path: '', component: LoginComponent},
    {path: 'blogs', component: BlogsComponent},
];
