import { ApplicationConfig , provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';

import { routes } from './app.routes';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import {  provideHttpClient, withFetch } from '@angular/common/http';
import { CommonModule } from '@angular/common';

 export const appConfig: ApplicationConfig = {
  providers: [provideZoneChangeDetection({ eventCoalescing: true }), provideRouter(routes),FormsModule,ReactiveFormsModule,CommonModule,provideHttpClient(withFetch())]
};
