import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface UsuarioAdmin {
  id: number;
  rol_id: number;
  rol: string;
  nombres: string;
  apellidos: string;
  documento: string;
  correo: string;
  telefono: string | null;
  activo: number;
}

export interface RolUsuario {
  id: number;
  nombre: string;
}

export interface DatosUsuario {
  rol_id: number;
  nombres: string;
  apellidos: string;
  documento: string;
  correo: string;
  telefono: string;
  password?: string;
}

interface RespuestaUsuarios {
  usuarios: UsuarioAdmin[];
  roles: RolUsuario[];
}

interface RespuestaUsuario {
  message: string;
}

@Injectable({ providedIn: 'root' })
export class UsuariosService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/usuarios.php';

  listar(): Observable<RespuestaUsuarios> {
    return this.http.get<RespuestaUsuarios>(this.endpoint, { withCredentials: true });
  }

  crear(datos: DatosUsuario): Observable<RespuestaUsuario> {
    return this.http.post<RespuestaUsuario>(this.endpoint, datos, { withCredentials: true });
  }

  actualizar(id: number, datos: DatosUsuario): Observable<RespuestaUsuario> {
    return this.http.put<RespuestaUsuario>(`${this.endpoint}?id=${id}`, datos, { withCredentials: true });
  }

  cambiarEstado(id: number, activo: boolean): Observable<RespuestaUsuario> {
    return this.http.patch<RespuestaUsuario>(`${this.endpoint}?id=${id}`, { activo }, { withCredentials: true });
  }
}
