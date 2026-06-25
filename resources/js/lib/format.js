// Helpers de formato — CLP (pesos chilenos) y afines.
// CLP no usa decimales y separa miles con punto: 1234567 -> "$1.234.567".

const nf = new Intl.NumberFormat('es-CL');

export const clp = (n) => '$' + nf.format(Math.round(Number(n) || 0));

export const entero = (n) => nf.format(Math.round(Number(n) || 0));

export const fecha = (d) => {
    if (!d) return '';
    const date = new Date(d);
    if (Number.isNaN(date.getTime())) return d;
    return date.toLocaleDateString('es-CL');
};

export const fechaHora = (d) => {
    if (!d) return '';
    const date = new Date(d);
    if (Number.isNaN(date.getTime())) return d;
    return date.toLocaleString('es-CL');
};

// Resuelve "tipo.descripcion" sobre un objeto fila.
export const getPath = (obj, path) =>
    path.split('.').reduce((acc, key) => (acc == null ? acc : acc[key]), obj);

// Formatea el valor de una columna según su tipo.
export const formatCol = (value, format) => {
    switch (format) {
        case 'clp':    return clp(value);
        case 'entero': return entero(value);
        case 'fecha':  return fecha(value);
        case 'bool':   return value ? 'Sí' : 'No';
        default:       return value ?? '';
    }
};
