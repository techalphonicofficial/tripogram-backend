@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function downloadBusImage(button) {
    let busCard = button.closest('.bus-card');
    html2canvas(busCard).then(canvas => {
        let link = document.createElement('a');
        link.download = 'bus-seat-layout.png';
        link.href = canvas.toDataURL();
        link.click();
    });
}
</script>
@endpush

@push('styles')
<style>
.main-grid {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}
.buses-section { flex: 2; }
.bookings-section { flex: 1; }

.bus-card {
    border: 1px solid #ccc;
    padding: 1rem;
    margin-bottom: 2rem;
    border-radius: 8px;
    background: #f9f9f9;
}

.bus-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.seat-layout {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.seat-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.passage { flex: 0 0 1rem; }

.seat {
    display: inline-block;
    text-align: center;
    cursor: pointer;
}
.seat input { display: none; }
.seat-number {
    display: block;
    width: 30px;
    height: 30px;
    line-height: 30px;
    border: 1px solid #555;
    border-radius: 4px;
}
.seat-booked .seat-number { background: #ccc; cursor: not-allowed; }
.seat-selected .seat-number { background: #4ade80; color: #fff; }

.assigned-booking {
    margin-bottom: 1rem;
    padding: 0.5rem;
    background: #fff;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.booking-card {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    border: 1px solid #ddd;
}

.booking-active { background: #4ade80; color: #fff; }
.booking-inactive { background: #f0f0f0; }
</style>
@endpush
