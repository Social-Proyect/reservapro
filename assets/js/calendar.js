// Clase Calendar para manejar el calendario
class Calendar {
    constructor(container, onDateSelect) {
        this.container = container;
        this.onDateSelect = onDateSelect;
        this.currentDate = new Date();
        this.selectedDate = null;
        this.availableDates = [];
        this.render();
    }

    setAvailableDates(dates) {
        this.availableDates = dates;
        this.render();
    }

    render() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        const monthNames = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];

        const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        // Header
        const header = `
            <div class="calendar-header">
                <h3>${monthNames[month]} ${year}</h3>
                <div class="calendar-nav">
                    <button id="prev-month">←</button>
                    <button id="next-month">→</button>
                </div>
            </div>
        `;

        // Days header
        let daysHeader = '<div class="calendar-grid">';
        dayNames.forEach(day => {
            daysHeader += `<div class="calendar-day-header">${day}</div>`;
        });

        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        // Previous month days
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            daysHeader += `<div class="calendar-day other-month">${day}</div>`;
        }

        // Current month days
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            date.setHours(0, 0, 0, 0);
            
            const dateStr = date.toISOString().split('T')[0];
            const isAvailable = this.availableDates.includes(dateStr);
            const isPast = date < today;
            const isSelected = this.selectedDate && this.selectedDate.toDateString() === date.toDateString();

            let classes = 'calendar-day';
            if (isPast && !isAvailable) {
                classes += ' disabled';
            } else if (isAvailable) {
                classes += ' available';
            }
            if (isSelected) {
                classes += ' selected';
            }

            daysHeader += `<div class="${classes}" data-date="${dateStr}">${day}</div>`;
        }

        // Next month days
        const totalCells = firstDay + daysInMonth;
        const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
        for (let day = 1; day <= remainingCells; day++) {
            daysHeader += `<div class="calendar-day other-month">${day}</div>`;
        }

        daysHeader += '</div>';

        this.container.innerHTML = header + daysHeader;

        // Event listeners
        document.getElementById('prev-month').addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.render();
        });

        document.getElementById('next-month').addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.render();
        });

        // Day click events
        this.container.querySelectorAll('.calendar-day:not(.disabled):not(.other-month)').forEach(day => {
            day.addEventListener('click', (e) => {
                const dateStr = e.target.getAttribute('data-date');
                if (dateStr) {
                    this.selectedDate = new Date(dateStr + 'T00:00:00');
                    this.render();
                    if (this.onDateSelect) {
                        this.onDateSelect(dateStr);
                    }
                }
            });
        });
    }
}
