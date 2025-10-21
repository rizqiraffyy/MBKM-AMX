//SHOWSECTION
function showSection(sectionId) {
  // Menyembunyikan semua <section> dengan menghapus class 'active'
  const sections = document.querySelectorAll('section');
  sections.forEach(section => {
      section.classList.remove('active');
  });

  // Menampilkan section yang dipilih dengan menambahkan class 'active'
  const selectedSection = document.getElementById(sectionId);
  selectedSection.classList.add('active');
}

//Address Indonesia
fetch(`https://kanglerian.github.io/api-wilayah-indonesia/api/provinces.json`)
  .then(response => response.json())
  .then(provinces => {
    var data = provinces;
    var tampung = '<option>Choose Province</option>';
    data.forEach(element => {
      tampung += `<option data-reg="${element.id}" value="${element.name}">${element.name}</option>`;
    });
    document.getElementById('province').innerHTML = tampung;
  });

const selectProvince = document.getElementById('province');
selectProvince.addEventListener('change', (e) => {
  var province = e.target.options[e.target.selectedIndex].dataset.reg;
  fetch(`https://kanglerian.github.io/api-wilayah-indonesia/api/regencies/${province}.json`)
    .then(response => response.json())
    .then(regencies => {
      var data = regencies;
      var tampung = '<option>Choose Regency</option>';
      data.forEach(element => {
        tampung += `<option data-dist="${element.id}" value="${element.name}">${element.name}</option>`;
      });
      document.getElementById('regency').innerHTML = tampung;
    });
});

const selectRegency = document.getElementById('regency');
selectRegency.addEventListener('change', (e) => {
  var regency = e.target.options[e.target.selectedIndex].dataset.dist;
  fetch(`https://kanglerian.github.io/api-wilayah-indonesia/api/districts/${regency}.json`)
    .then(response => response.json())
    .then(districts => {
      var data = districts;
      var tampung = '<option>Choose District</option>';
      data.forEach(element => {
        tampung += `<option data-subdist="${element.id}" value="${element.name}">${element.name}</option>`;
      });
      document.getElementById('district').innerHTML = tampung;
    });
});

const selectDistrict = document.getElementById('district');
selectDistrict.addEventListener('change', (e) => {
  var district = e.target.options[e.target.selectedIndex].dataset.subdist;
  fetch(`https://kanglerian.github.io/api-wilayah-indonesia/api/villages/${district}.json`)
    .then(response => response.json())
    .then(villages => {
      var data = villages;
      var tampung = '<option>Choose Sub-District</option>';
      data.forEach(element => {
        tampung += `<option value="${element.name}">${element.name}</option>`;
      });
      document.getElementById('subdistrict').innerHTML = tampung;
    });
});

// Fungsi untuk mendapatkan tanggal hari ini dalam format YYYY-MM-DD
function setTodayDate() {
    const today = new Date();
    const day = String(today.getDate()).padStart(2, '0');
    const month = String(today.getMonth() + 1).padStart(2, '0'); // Bulan dimulai dari 0
    const year = today.getFullYear();
    const todayDate = `${year}-${month}-${day}`;

    // Set nilai input dengan tanggal hari ini
    document.getElementById('date-input').value = todayDate;
}

// Memanggil fungsi setTodayDate saat halaman dimuat
window.onload = setTodayDate;
