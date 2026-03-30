// assets/js/app.js

document.addEventListener('DOMContentLoaded', function () {

    var alerts = document.querySelectorAll('.alert.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            try {
                var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            } catch (e) {}
        }, 4000);
    });

    document.querySelectorAll('.modal').forEach(function (modal) {

        modal.addEventListener('show.bs.modal', function () {
            var modalBody = this.querySelector('.modal-body');
            if (modalBody) modalBody.scrollTop = 0;

            this.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                el.style.pointerEvents = 'all';
                el.style.position = 'relative';
                el.style.zIndex = '10';
            });
        });

        modal.addEventListener('hidden.bs.modal', function () {
            document.body.style.overflow     = '';
            document.body.style.paddingRight = '';
            document.body.classList.remove('modal-open');
            document.querySelectorAll('.modal-backdrop').forEach(function (bd) {
                bd.remove();
            });
        });

    });

});

function editSemester(id, nama, tahun, status) {
    document.getElementById('edit_semester_id').value    = id;
    document.getElementById('edit_semester_nama').value  = nama;
    document.getElementById('edit_semester_tahun').value = tahun;
    var sel = document.getElementById('edit_semester_status');
    for (var i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = (sel.options[i].value === status);
    }
}

function editMataKuliah(id, nama, sks, hari, jam, dosen) {
    document.getElementById('edit_mk_id').value    = id;
    document.getElementById('edit_mk_nama').value  = nama;
    document.getElementById('edit_mk_sks').value   = sks;
    document.getElementById('edit_mk_dosen').value = dosen;
    document.getElementById('edit_mk_jam').value   = jam;
    var sel = document.getElementById('edit_mk_hari');
    for (var i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = (sel.options[i].value === hari);
    }
}

function editTugas(id, nama, deadline, nilai) {
    document.getElementById('edit_tugas_id').value       = id;
    document.getElementById('edit_tugas_nama').value     = nama;
    document.getElementById('edit_tugas_deadline').value = deadline;
    document.getElementById('edit_tugas_nilai').value    = nilai;
}