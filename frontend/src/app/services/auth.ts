import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, timeout } from 'rxjs';

export interface AuthUser {
  id: number;
  nombres: string;
  apellidos: string;
  correo: string;
  rol: string;
  documento?: string;
  telefono?: string | null;
}

interface AuthResponse {
  message: string;
  user?: AuthUser;
  authenticated?: boolean;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/auth.php';
  private readonly requestTimeout = 8000;

  login(correo: string, password: string): Observable<AuthResponse> {
    return this.http
      .post<AuthResponse>(`${this.endpoint}?action=login`, { correo, password }, { withCredentials: true })
      .pipe(timeout({ first: this.requestTimeout }));
  }

  currentUser(): Observable<AuthResponse> {
    return this.http
      .get<AuthResponse>(`${this.endpoint}?action=me`, { withCredentials: true })
      .pipe(timeout({ first: this.requestTimeout }));
  }

  profile(): Observable<AuthResponse> {
    return this.http
      .get<AuthResponse>(`${this.endpoint}?action=profile`, { withCredentials: true })
      .pipe(timeout({ first: this.requestTimeout }));
  }

  changePassword(currentPassword: string, newPassword: string): Observable<AuthResponse> {
    return this.http
      .post<AuthResponse>(`${this.endpoint}?action=change-password`, {
        current_password: currentPassword,
        new_password: newPassword,
      }, { withCredentials: true })
      .pipe(timeout({ first: this.requestTimeout }));
  }

  register(data: {
    nombres: string;
    apellidos: string;
    documento: string;
    correo: string;
    telefono: string;
    password: string;
  }): Observable<AuthResponse> {
    return this.http
      .post<AuthResponse>(`${this.endpoint}?action=register`, data, { withCredentials: true })
      .pipe(timeout({ first: this.requestTimeout }));
  }

  logout(): Observable<AuthResponse> {
    return this.http
      .post<AuthResponse>(`${this.endpoint}?action=logout`, {}, { withCredentials: true })
      .pipe(timeout({ first: this.requestTimeout }));
  }
}
