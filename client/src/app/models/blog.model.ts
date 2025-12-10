export type BlogCategory =
  | 'Technology'
  | 'Health'
  | 'Science'
  | 'Business'
  | 'Entertainment'
  | 'Sports'
  | 'Education'
  | 'Lifestyle'
  | 'Politics'
  | 'Travel';

// Blog entity
export interface Blog {
  id: number;
  title: string;
  description: string;
  category: BlogCategory;
  author: string;
  createdAt: string;
  updatedAt?: string;
}

// DTOs
export interface CreateBlogRequest {
  title: string;
  description: string;
  category: BlogCategory;
}

export interface UpdateBlogRequest extends CreateBlogRequest {
  id: number;
}
