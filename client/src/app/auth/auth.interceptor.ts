import { HttpInterceptorFn } from '@angular/common/http';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  // Get the token from local storage
  const token = localStorage.getItem('token');

  if (token) {
    // Clone the request and add the Authorization header
    const authReq = req.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`,
      },
    });

    // Pass the modified request to the next handler
    return next(authReq);
  }

  // If no token, pass the original request
  return next(req);
};