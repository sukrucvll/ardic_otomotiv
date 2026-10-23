document.addEventListener('DOMContentLoaded', () => {
    // Bildirimleri (toast) göster
    document.querySelectorAll('.toast').forEach((el) => bootstrap.Toast.getOrCreateInstance(el).show());

    // Tooltip'ler
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));

    // data-confirm özelliği taşıyan formlar gönderilmeden önce onay penceresi açar
    const modalEl = document.getElementById('confirmModal');

    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        const messageEl = modalEl.querySelector('[data-confirm-message]');
        const titleEl = modalEl.querySelector('[data-confirm-title]');
        const okButton = modalEl.querySelector('[data-confirm-ok]');
        let pendingForm = null;

        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                pendingForm = form;
                titleEl.textContent = form.dataset.confirmTitle || 'Emin misiniz?';
                messageEl.textContent = form.dataset.confirm;
                okButton.textContent = form.dataset.confirmButton || 'Onayla';
                modal.show();
            });
        });

        okButton.addEventListener('click', () => {
            if (pendingForm) {
                okButton.disabled = true;
                pendingForm.submit();
            }
        });
    }

    // Atama tarihlerini düzenleme penceresi: tıklanan satırın bilgileriyle doldurulur
    const datesModalEl = document.getElementById('assignmentDatesModal');

    if (datesModalEl) {
        const datesModal = new bootstrap.Modal(datesModalEl);
        const datesForm = datesModalEl.querySelector('form');
        const dueInput = datesForm.querySelector('[name="due_date"]');
        const completedInput = datesForm.querySelector('[name="completed_on"]');
        const completedField = datesForm.querySelector('[data-completed-field]');

        document.querySelectorAll('[data-edit-assignment]').forEach((button) => {
            button.addEventListener('click', () => {
                const { action, title, dueDate, completedOn } = button.dataset;

                datesForm.action = action;
                datesModalEl.querySelector('[data-assignment-title]').textContent = title;
                dueInput.value = dueDate || '';
                completedInput.value = completedOn || '';

                // Tamamlanma tarihi yalnızca tamamlanmış atamada düzenlenir
                completedInput.disabled = !completedOn;
                completedField.classList.toggle('d-none', !completedOn);

                datesModal.show();
            });
        });
    }

    // Panoya kopyalama
    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.querySelector(button.dataset.copyTarget);
            const original = button.innerHTML;

            try {
                await navigator.clipboard.writeText(target.value ?? target.textContent);
                button.innerHTML = '<i class="bi bi-check2"></i> Kopyalandı';
            } catch {
                target.select?.();
                button.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ctrl+C ile kopyalayın';
            }

            setTimeout(() => (button.innerHTML = original), 2000);
        });
    });
});
