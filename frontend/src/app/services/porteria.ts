import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class PorteriaService {
  private readonly http = inject(HttpClient);

  //TODO: Cambiar la url de la api a la url que cada quien tenga
  private readonly api = 'http://localhost:8000/controllers/porteria.php';

  buscar(documento: string) {
    return this.http.get(`${this.api}?control=buscar&documento=${encodeURIComponent(documento)}`, { withCredentials: true });
  }

  ingreso(datos: any) {
    return this.http.post(`${this.api}?control=ingreso`, datos, { withCredentials: true });
  }

  salida(datos: any) {
    return this.http.post(`${this.api}?control=salida`, datos, { withCredentials: true });
  }

  dentro() {
    return this.http.get(`${this.api}?control=dentro`, { withCredentials: true });
  }

  parqueaderos() {
    return this.http.get(`${this.api}?control=parqueaderos`, { withCredentials: true });
  }

  crearParqueadero(codigo: string) {
    return this.http.post(`${this.api}?control=parqueadero_insertar`, { codigo }, { withCredentials: true });
  }

  editarParqueadero(id: number, codigo: string) {
    return this.http.post(`${this.api}?control=parqueadero_editar`, { id, codigo }, { withCredentials: true });
  }

  eliminarParqueadero(id: number) {
    return this.http.post(`${this.api}?control=parqueadero_eliminar`, { id }, { withCredentials: true });
  }

  bitacora(fecha: string, torre: string, apartamento: string) {
    const filtros = `&fecha=${encodeURIComponent(fecha)}&torre=${encodeURIComponent(torre)}&apartamento=${encodeURIComponent(apartamento)}`;
    return this.http.get(`${this.api}?control=bitacora${filtros}`, { withCredentials: true });
  }
}
