import { Routes } from '@angular/router';
import { Login } from './modules/login/login';
import { Registro } from './modules/registro/registro';
import { Inicio } from './modules/inicio/inicio';
import { Visitantes } from './modules/visitantes/visitantes';
import { Porteria } from './modules/porteria/porteria';
import { Zonas } from './modules/zonas/zonas';
import { Cuotas } from './modules/cuotas/cuotas';
import { Reservas } from './modules/reservas/reservas';
import { Usuarios } from './modules/usuarios/usuarios';
import { Inmuebles } from './modules/inmuebles/inmuebles';
import { Residencias } from './modules/residencias/residencias';
import { Perfil } from './modules/perfil/perfil';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  { path: 'login', component: Login, title: 'Iniciar sesión' },
  { path: 'registro', component: Registro, title: 'Crear cuenta' },
  { path: 'inicio', component: Inicio, title: 'Inicio' },
  { path: 'visitantes', component: Visitantes, title: 'Preautorizar visitante' },
  { path: 'porteria', component: Porteria, title: 'Porteria' },
  { path: 'zonas', component: Zonas, title: 'Zonas comunes' },
  { path: 'reservas', component: Reservas, title: 'Reservas de zonas comunes' },
  { path: 'cuotas', component: Cuotas, title: 'Cuotas' },
  { path: 'usuarios', component: Usuarios, title: 'Usuarios' },
  { path: 'inmuebles', component: Inmuebles, title: 'Torres y apartamentos' },
  { path: 'residencias', component: Residencias, title: 'Residentes y apartamentos' },
  { path: 'perfil', component: Perfil, title: 'Mi perfil' },
  { path: '**', redirectTo: 'login' },
];
