document.addEventListener('DOMContentLoaded', () => {
    const state = {
        rows: [],
        filtered: []
    };

    const elements = {
        total: document.getElementById('totalBookings'),
        upcoming: document.getElementById('upcomingBookings'),
        completed: document.getElementById('completedBookings'),
        pending: document.getElementById('pendingBookings'),
        statusBadge: document.getElementById('dataStatusBadge'),
        trend: document.getElementById('trendChart'),
        tableBody: document.getElementById('bookingsTableBody'),
        empty: document.getElementById('bookingsEmpty'),
        search: document.getElementById('bookingSearch'),
        startDate: document.getElementById('bookingStartDate'),
        endDate: document.getElementById('bookingEndDate'),
        statusFilter: document.getElementById('bookingStatusFilter'),
        exportBtn: document.querySelector('[data-action="export"]'),
        addBtn: document.querySelector('[data-action="add"]'),
        remindBtn: document.querySelector('[data-action="remind"]'),
        trendLabel: document.getElementById('trendRangeLabel'),
        modal: document.getElementById('bookingModal'),
        modalBackdrop: document.getElementById('bookingModalBackdrop'),
        modalClose: document.getElementById('bookingModalClose'),
        modalSubtitle: document.getElementById('bookingModalSubtitle'),
        modalBody: document.getElementById('bookingModalBody')
    };

    const BUSINESS_WHATSAPP = '918390246575';

    function showToast(message, tone = 'success') {
        const toast = document.createElement('div');
        const toneClass = tone === 'error' ? 'bg-red-600' : 'bg-emerald-600';
        toast.className = `fixed top-4 right-4 px-4 py-3 rounded-lg text-white shadow-lg z-50 ${toneClass}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 2500);
    }

    function setStatus(text, tone) {
        if (!elements.statusBadge) return;
        elements.statusBadge.textContent = text;
        elements.statusBadge.className = `inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ${tone}`;
    }

    function parseDate(value) {
        if (!value) return null;
        const trimmed = String(value).trim();
        if (!trimmed) return null;

        const isoMatch = trimmed.match(/^\d{4}-\d{2}-\d{2}/);
        if (isoMatch) {
            return new Date(isoMatch[0] + 'T00:00:00');
        }

        const shortMatch = trimmed.match(/^(\d{1,2})-([A-Za-z]{3})-(\d{4})/);
        if (shortMatch) {
            const day = Number(shortMatch[1]);
            const month = shortMatch[2].toLowerCase();
            const year = Number(shortMatch[3]);
            const months = {
                jan: 0, feb: 1, mar: 2, apr: 3, may: 4, jun: 5,
                jul: 6, aug: 7, sep: 8, oct: 9, nov: 10, dec: 11
            };
            if (months[month] !== undefined) {
                return new Date(year, months[month], day);
            }
        }

        const parsed = Date.parse(trimmed);
        if (!Number.isNaN(parsed)) {
            return new Date(parsed);
        }

        return null;
    }

    function formatDateDisplay(value) {
        const dateObj = parseDate(value);
        if (!dateObj) return value || '-';
        return dateObj.toISOString().slice(0, 10);
    }

    function normalizeStatus(status) {
        if (!status) return 'Pending';
        const clean = String(status).trim().toLowerCase();
        if (!clean) return 'Pending';
        return clean.charAt(0).toUpperCase() + clean.slice(1);
    }

    function statusBadge(status) {
        const normalized = normalizeStatus(status);
        if (normalized === 'Completed') {
            return '<span class="inline-flex items-center rounded-full bg-slate-200 text-slate-700 px-2.5 py-1 text-xs font-semibold">Completed</span>';
        }
        if (normalized === 'Confirmed') {
            return '<span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1 text-xs font-semibold">Confirmed</span>';
        }
        return '<span class="inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2.5 py-1 text-xs font-semibold">Pending</span>';
    }

    function matchesSearch(row, query) {
        if (!query) return true;
        const haystack = [row.name, row.phone, row.service].join(' ').toLowerCase();
        return haystack.includes(query.toLowerCase());
    }

    function matchesDateRange(row, startValue, endValue) {
        if (!startValue && !endValue) return true;
        const rowDate = parseDate(row.date);
        if (!rowDate) return false;

        const rowIso = rowDate.toISOString().slice(0, 10);
        if (startValue && rowIso < startValue) return false;
        if (endValue && rowIso > endValue) return false;
        return true;
    }

    function matchesStatus(row, status) {
        if (!status || status === 'all') return true;
        return normalizeStatus(row.status).toLowerCase() === status;
    }

    function applyFilters() {
        const query = elements.search ? elements.search.value.trim() : '';
        const startValue = elements.startDate ? elements.startDate.value : '';
        const endValue = elements.endDate ? elements.endDate.value : '';
        const status = elements.statusFilter ? elements.statusFilter.value : 'all';

        state.filtered = state.rows.filter((row) => (
            matchesSearch(row, query) &&
            matchesDateRange(row, startValue, endValue) &&
            matchesStatus(row, status)
        ));

        renderTable();
        renderStats();
        renderTrend();
    }

    function renderStats() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const total = state.filtered.length;
        const upcoming = state.filtered.filter((row) => {
            const d = parseDate(row.date);
            if (!d) return false;
            d.setHours(0, 0, 0, 0);
            return d >= today && normalizeStatus(row.status) !== 'Completed';
        }).length;

        const completed = state.filtered.filter((row) => {
            if (normalizeStatus(row.status) === 'Completed') return true;
            const d = parseDate(row.date);
            if (!d) return false;
            d.setHours(0, 0, 0, 0);
            return d < today;
        }).length;

        const pending = state.filtered.filter((row) => normalizeStatus(row.status) === 'Pending').length;

        if (elements.total) elements.total.textContent = String(total);
        if (elements.upcoming) elements.upcoming.textContent = String(upcoming);
        if (elements.completed) elements.completed.textContent = String(completed);
        if (elements.pending) elements.pending.textContent = String(pending);
    }

    function renderTable() {
        if (!elements.tableBody) return;
        elements.tableBody.innerHTML = '';

        if (!state.filtered.length) {
            if (elements.empty) {
                elements.empty.classList.remove('hidden');
            }
            return;
        }

        if (elements.empty) {
            elements.empty.classList.add('hidden');
        }

        const fragment = document.createDocumentFragment();
        state.filtered.forEach((row) => {
            const tr = document.createElement('tr');
            tr.dataset.source = row.source_file || '';
            tr.dataset.index = String(row.row_index ?? '');
            tr.dataset.id = row.id || '';
            const normalizedStatus = normalizeStatus(row.status);
            tr.innerHTML = `
                <td class="py-3 px-4">${formatDateDisplay(row.date)}</td>
                <td class="py-3 px-4 font-semibold">${row.name || '-'}</td>
                <td class="py-3 px-4">${row.phone || '-'}</td>
                <td class="py-3 px-4">${row.service || '-'}</td>
                <td class="py-3 px-4">${row.slot || '-'}</td>
                <td class="py-3 px-4">
                    <div class="flex items-center gap-2">
                        <select data-field="status" class="border border-slate-200 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-red-200">
                            <option value="pending" ${normalizedStatus === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="confirmed" ${normalizedStatus === 'Confirmed' ? 'selected' : ''}>Confirmed</option>
                            <option value="completed" ${normalizedStatus === 'Completed' ? 'selected' : ''}>Completed</option>
                        </select>
                        ${statusBadge(row.status)}
                    </div>
                </td>
                <td class="py-3 px-4">
                    <div class="flex items-center gap-2">
                        <button data-action="view" class="text-xs font-semibold text-slate-700 hover:text-red-700">View</button>
                    </div>
                </td>
            `;
            fragment.appendChild(tr);
        });

        elements.tableBody.appendChild(fragment);
    }

    function renderTrend() {
        if (!elements.trend) return;
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const startValue = elements.startDate ? elements.startDate.value : '';
        const endValue = elements.endDate ? elements.endDate.value : '';

        let startDate = startValue ? new Date(startValue + 'T00:00:00') : null;
        let endDate = endValue ? new Date(endValue + 'T00:00:00') : null;

        if (!startDate && !endDate) {
            endDate = new Date(today);
            startDate = new Date(today);
            startDate.setDate(startDate.getDate() - 13);
        } else if (startDate && !endDate) {
            endDate = new Date(today);
        } else if (!startDate && endDate) {
            startDate = new Date(endDate);
            startDate.setDate(startDate.getDate() - 13);
        }

        if (startDate > endDate) {
            const swap = startDate;
            startDate = endDate;
            endDate = swap;
        }

        const days = Math.min(31, Math.round((endDate - startDate) / 86400000) + 1);
        const buckets = [];
        for (let i = 0; i < days; i += 1) {
            const d = new Date(startDate);
            d.setDate(startDate.getDate() + i);
            buckets.push({
                date: d,
                count: 0
            });
        }

        state.filtered.forEach((row) => {
            const d = parseDate(row.date);
            if (!d) return;
            d.setHours(0, 0, 0, 0);
            buckets.forEach((bucket) => {
                if (bucket.date.getTime() === d.getTime()) {
                    bucket.count += 1;
                }
            });
        });

        const max = Math.max(...buckets.map((b) => b.count), 1);
        const bars = buckets.map((b) => {
            const height = Math.round((b.count / max) * 100);
            return `
                <div class="flex flex-col items-center gap-1" style="width: 12px;">
                    <div class="w-full rounded-full bg-red-200" style="height: ${Math.max(height, 8)}px"></div>
                    <span class="text-[10px] text-slate-400">${String(b.date.getDate()).padStart(2, '0')}</span>
                </div>
            `;
        }).join('');

        elements.trend.innerHTML = `<div class="flex items-end gap-1">${bars}</div>`;

        if (elements.trendLabel) {
            const labelStart = startDate.toISOString().slice(0, 10);
            const labelEnd = endDate.toISOString().slice(0, 10);
            elements.trendLabel.textContent = `${labelStart} to ${labelEnd}`;
        }
    }

    function openModal(row) {
        if (!elements.modal || !elements.modalBody || !elements.modalSubtitle) return;
        elements.modalSubtitle.textContent = `${row.name || 'Unknown'} • ${formatDateDisplay(row.date)}`;

        const primary = [
            ['Booking ID', row.id || '-'],
            ['Patient', row.name || '-'],
            ['Contact', row.phone || '-'],
            ['Service', row.service || '-'],
            ['Slot', row.slot || '-'],
            ['Status', normalizeStatus(row.status)],
            ['Created At', row.created_at || '-'],
            ['Source File', row.source_file || '-']
        ];

        const primaryHtml = primary.map(([label, value]) => (
            `<div class="flex justify-between gap-4 border-b border-slate-100 py-2">
                <span class="text-xs uppercase tracking-wide text-slate-400">${label}</span>
                <span class="text-sm font-semibold text-slate-700">${value}</span>
            </div>`
        )).join('');

        const raw = row.raw || {};
        const rawEntries = Object.keys(raw).map((key) => (
            `<div class="flex justify-between gap-4 border-b border-slate-100 py-2">
                <span class="text-xs uppercase tracking-wide text-slate-400">${key}</span>
                <span class="text-sm text-slate-700">${raw[key] || '-'}</span>
            </div>`
        )).join('');

        elements.modalBody.innerHTML = `
            <div class="space-y-4">
                <div>
                    <h4 class="font-[Poppins] font-semibold text-slate-800">Summary</h4>
                    <div class="mt-2 rounded-xl border border-slate-100 px-4">${primaryHtml}</div>
                </div>
                <div>
                    <h4 class="font-[Poppins] font-semibold text-slate-800">Raw Data</h4>
                    <div class="mt-2 rounded-xl border border-slate-100 px-4">${rawEntries}</div>
                </div>
            </div>
        `;

        elements.modal.classList.remove('hidden');
        elements.modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        if (!elements.modal) return;
        elements.modal.classList.add('hidden');
        elements.modal.setAttribute('aria-hidden', 'true');
    }

    async function updateStatus(row, newStatus) {
        try {
            const response = await fetch('../php/admin-update-booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    source_file: row.source_file,
                    row_index: row.row_index,
                    status: newStatus
                })
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to update status');
            }
            row.status = newStatus;
            showToast('Status updated', 'success');
            applyFilters();
        } catch (error) {
            showToast('Status update failed', 'error');
        }
    }

    function exportCsv() {
        if (!state.filtered.length) return;
        const headers = ['Date', 'Patient', 'Contact', 'Service', 'Slot', 'Status', 'Source'];
        const lines = [headers.join(',')];
        state.filtered.forEach((row) => {
            const line = [
                formatDateDisplay(row.date),
                row.name || '',
                row.phone || '',
                row.service || '',
                row.slot || '',
                normalizeStatus(row.status),
                row.source_file || ''
            ].map((value) => `"${String(value).replace(/"/g, '""')}"`);
            lines.push(line.join(','));
        });

        const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'bookings_export.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
    }

    async function loadData() {
        try {
            setStatus('Loading data...', 'bg-slate-100 text-slate-600');
            const response = await fetch('../php/admin-bookings.php', { cache: 'no-store' });
            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error('Unable to load bookings');
            }

            state.rows = Array.isArray(payload.rows) ? payload.rows : [];
            state.filtered = [...state.rows];

            setStatus(state.rows.length ? 'Live data' : 'No bookings yet', 'bg-emerald-50 text-emerald-700');
            applyFilters();
        } catch (error) {
            setStatus('Data offline', 'bg-red-50 text-red-600');
            if (elements.tableBody) {
                elements.tableBody.innerHTML = '';
            }
            if (elements.empty) {
                elements.empty.classList.remove('hidden');
            }
        }
    }

    if (elements.search) {
        elements.search.addEventListener('input', applyFilters);
    }
    if (elements.startDate) {
        elements.startDate.addEventListener('change', () => {
            applyFilters();
            renderTrend();
        });
    }
    if (elements.endDate) {
        elements.endDate.addEventListener('change', () => {
            applyFilters();
            renderTrend();
        });
    }
    if (elements.statusFilter) {
        elements.statusFilter.addEventListener('change', applyFilters);
    }

    if (elements.exportBtn) {
        elements.exportBtn.addEventListener('click', exportCsv);
    }

    if (elements.addBtn) {
        elements.addBtn.addEventListener('click', () => {
            window.location.href = '../contact.html#booking';
        });
    }

    if (elements.remindBtn) {
        elements.remindBtn.addEventListener('click', () => {
            window.open(`https://wa.me/${BUSINESS_WHATSAPP}`, '_blank', 'noopener,noreferrer');
        });
    }

    if (elements.tableBody) {
        elements.tableBody.addEventListener('click', (event) => {
            const action = event.target.closest('[data-action]');
            if (!action) return;
            const tr = action.closest('tr');
            if (!tr) return;

            const source = tr.dataset.source || '';
            const index = Number(tr.dataset.index);
            const row = state.filtered.find((item) => item.source_file === source && item.row_index === index);
            if (!row) return;

            if (action.dataset.action === 'view') {
                openModal(row);
            }
        });

        elements.tableBody.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLSelectElement)) return;
            if (target.dataset.field !== 'status') return;
            const tr = target.closest('tr');
            if (!tr) return;

            const source = tr.dataset.source || '';
            const index = Number(tr.dataset.index);
            const row = state.filtered.find((item) => item.source_file === source && item.row_index === index);
            if (!row) return;

            updateStatus(row, target.value);
        });
    }

    if (elements.modalClose) {
        elements.modalClose.addEventListener('click', closeModal);
    }
    if (elements.modalBackdrop) {
        elements.modalBackdrop.addEventListener('click', closeModal);
    }

    loadData();
});
