// document.addEventListener("alpine:init", () => {
//     Alpine.data("economicCalendar", (initialEvents, serverTime, isToday) => ({
//         // Datos
//         events: initialEvents,
//         currentTime: serverTime,
//         isToday: isToday,

//         // Estado de Filtros (Arrays vacíos = "Cualquiera")
//         selectedImpacts: [],
//         selectedCurrencies: [],

//         // Getter Computado: Filtra la lista al instante
//         get filteredEvents() {
//             return this.events.filter((e) => {
//                 // Lógica Impacto: Si está vacío (todos) O incluye el impacto
//                 const matchImpact =
//                     this.selectedImpacts.length === 0 ||
//                     this.selectedImpacts.includes(e.impact);
//                 // Lógica Divisa: Si está vacío (todos) O incluye la divisa
//                 const matchCurrency =
//                     this.selectedCurrencies.length === 0 ||
//                     this.selectedCurrencies.includes(e.currency);

//                 return matchImpact && matchCurrency;
//             });
//         },

//         // Función para saber si pintar la línea roja antes de la fila 'index'
//         showTimeLine(index) {
//             if (!this.isToday) return false;

//             const current = this.filteredEvents[index];

//             // Si es el primero de la lista y es futuro -> Línea arriba
//             if (index === 0) {
//                 return current.time_raw > this.currentTime;
//             }

//             // Si el anterior ya pasó Y el actual es futuro -> Línea en medio
//             const prev = this.filteredEvents[index - 1];
//             return (
//                 prev.time_raw <= this.currentTime &&
//                 current.time_raw > this.currentTime
//             );
//         },

//         // Helpers para limpiar
//         toggleImpact(val) {
//             if (this.selectedImpacts.includes(val)) {
//                 this.selectedImpacts = this.selectedImpacts.filter(
//                     (i) => i !== val,
//                 );
//             } else {
//                 this.selectedImpacts.push(val);
//             }
//         },
//         toggleCurrency(val) {
//             if (this.selectedCurrencies.includes(val)) {
//                 this.selectedCurrencies = this.selectedCurrencies.filter(
//                     (i) => i !== val,
//                 );
//             } else {
//                 this.selectedCurrencies.push(val);
//             }
//         },
//     }));
// });
