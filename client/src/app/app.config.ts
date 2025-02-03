import { ApplicationConfig , provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';

import { routes } from './app.routes';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import {  provideHttpClient, withFetch, withInterceptors } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import { authInterceptor } from './auth/auth.interceptor';

 export const appConfig: ApplicationConfig = {
  providers: [provideZoneChangeDetection({ eventCoalescing: true }), provideRouter(routes),FormsModule,ReactiveFormsModule,CommonModule,provideHttpClient(withInterceptors([authInterceptor]),withFetch()), provideAnimationsAsync()]
};
