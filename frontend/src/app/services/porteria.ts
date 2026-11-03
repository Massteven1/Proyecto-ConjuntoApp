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
}
