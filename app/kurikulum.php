<?php

function daftar_mapel(): array
{
    return [
        ['nama' => "Al-Qur'an", 'muatan' => 'agama'],
        ['nama' => 'Aqidah', 'muatan' => 'agama'],
        ['nama' => 'Akhlak', 'muatan' => 'agama'],
        ['nama' => 'Ibadah', 'muatan' => 'agama'],
        ['nama' => 'Tarikh', 'muatan' => 'agama'],
        ['nama' => 'Kemuhammadiyahan', 'muatan' => 'agama'],
        ['nama' => 'Bahasa Arab', 'muatan' => 'agama'],
        ['nama' => 'Tahsin Al-Qur\'an', 'muatan' => 'agama'],
        ['nama' => 'Tahfidz Al-Qur\'an', 'muatan' => 'agama'],
        ['nama' => 'Pendidikan Pancasila', 'muatan' => 'nasional'],
        ['nama' => 'Bahasa Indonesia', 'muatan' => 'nasional'],
        ['nama' => 'Matematika', 'muatan' => 'nasional'],
        ['nama' => 'Ilmu Pengetahuan Alam dan Sosial', 'muatan' => 'nasional'],
        ['nama' => 'Seni Rupa', 'muatan' => 'nasional'],
        ['nama' => 'Pendidikan Jasmani dan Olahraga', 'muatan' => 'nasional'],
        ['nama' => 'Bahasa Jawa', 'muatan' => 'lokal'],
        ['nama' => 'Bahasa Inggris', 'muatan' => 'lokal'],
        ['nama' => 'Batik', 'muatan' => 'lokal'],
    ];
}

function daftar_ekstra(): array
{
    return [
        ['nama' => 'Pramuka', 'urutan' => 1],
        ['nama' => 'Sepak Bola', 'urutan' => 2],
        ['nama' => 'Bola Basket', 'urutan' => 3],
        ['nama' => 'Seni Tari', 'urutan' => 4],
        ['nama' => 'Paduan Suara', 'urutan' => 5],
        ['nama' => 'Palang Merah Remaja', 'urutan' => 6],
        ['nama' => 'Jurnalistik', 'urutan' => 7],
        ['nama' => 'Kaligrafi', 'urutan' => 8],
        ['nama' => 'Paskibra', 'urutan' => 9],
        ['nama' => 'Komputer dan Teknologi', 'urutan' => 10],
    ];
}

function daftar_cp(): array
{
    return [
        "Al-Qur'an" => [
            'A' => 'Mengenal dan membaca huruf hijaiyah dengan benar serta melafalkan bacaan pendek sesuai adab membaca Al-Qur\'an.',
            'B' => 'Membaca Al-Qur\'an dengan lancar dan benar sesuai kaidah tajwid dasar serta menghafal surah pilihan.',
            'C' => 'Membaca dan murottal Al-Qur\'an dengan tajwid yang tepat serta memahami makna ayat pilihan.',
        ],
        'Aqidah' => [
            'A' => 'Mengenal sifat wajib dan mustahil bagi Allah serta rukun iman sederhana.',
            'B' => 'Memahami rukun Islam dan rukun iman serta meneladani kisah Nabi dan sahabat.',
            'C' => 'Menganalisis pengaruh akidah terhadap perilaku serta membahas isu keimanan kontekstual.',
        ],
        'Akhlak' => [
            'A' => 'Mengenal akhlak terpuji kepada orang tua, guru, dan teman.',
            'B' => 'Menerapkan akhlak terpuji dan menjauhi akhlak tercela dalam interaksi sosial.',
            'C' => 'Menganalisis perilaku sesuai nilai moral Islam dalam kehidupan bermasyarakat.',
        ],
        'Ibadah' => [
            'A' => 'Mengenal rukun Islam dan tata cara ibadah dasar.',
            'B' => 'Menjalankan ibadah wajib dan sunnah sesuai tuntunan serta memahami hikmahnya.',
            'C' => 'Menganalisis hukum dan hikmah ibadah serta menerapkannya secara istiqamah.',
        ],
        'Tarikh' => [
            'A' => 'Mengenal kisah para nabi dan awal sejarah Islam secara sederhana.',
            'B' => 'Menjelaskan periode kehidupan Nabi Muhammad SAW dan Khulafaur Rasyidin.',
            'C' => 'Menganalisis perkembangan peradaban Islam dan kontribusinya bagi peradaban dunia.',
        ],
        'Kemuhammadiyahan' => [
            'A' => 'Mengenal tokoh pendiri dan lambang Gerakan Muhammadiyah.',
            'B' => 'Memahami sejarah, ajaran, dan organisasi Muhammadiyah.',
            'C' => 'Menganalisis peran Muhammadiyah dalam pendidikan dan kemajuan bangsa.',
        ],
        'Bahasa Arab' => [
            'A' => 'Mengenal salam, sapaan, dan kosakata dasar bahasa Arab.',
            'B' => 'Memahami kalimat sederhana dan membaca teks pendek berbahasa Arab.',
            'C' => 'Membaca dan menulis teks bahasa Arab serta memahami ungkapan sehari-hari.',
        ],
        'Tahsin Al-Qur\'an' => [
            'A' => 'Mengenal makhraj huruf dan bacaan dasar dengan benar.',
            'B' => 'Menerapkan kaidah tajwid pada bacaan surah pendek.',
            'C' => 'Membaca Al-Qur\'an tartil dan sesuai kaidah tajwid tingkat lanjut.',
        ],
        'Tahfidz Al-Qur\'an' => [
            'A' => 'Menghafal surah pendek dan doa harian.',
            'B' => 'Menghafal surah pilihan dan menjaga hafalan melalui muraja\'ah.',
            'C' => 'Menambah dan memantapkan hafalan Al-Qur\'an serta mengamalkannya.',
        ],
        'Pendidikan Pancasila' => [
            'A' => 'Mengenal identitas diri, simbol negara, dan perilaku sesuai nilai Pancasila.',
            'B' => 'Memahami hak dan kewajiban warga serta norma dalam kehidupan bermasyarakat.',
            'C' => 'Menganalisis penerapan nilai Pancasila dan UUD NRI Tahun 1945 dalam kehidupan kontekstual.',
        ],
        'Bahasa Indonesia' => [
            'A' => 'Melakukan kegiatan membaca, menulis, menyimak, dan berbicara permulaan dengan bahasa yang tepat.',
            'B' => 'Membaca teks fiksi dan nonfiksi, menulis paragraf, serta berbicara terstruktur.',
            'C' => 'Menganalisis teks berbagai fungsi dan menulis teks eksposisi sederhana.',
        ],
        'Matematika' => [
            'A' => 'Menguasai bilangan hingga 1.000, penjumlahan dan pengurangan, pengukuran, serta pola.',
            'B' => 'Menguasai bilangan hingga 10.000, pecahan sederhana, geometri, dan penyajian data.',
            'C' => 'Menguasai bilangan bulat, pecahan, perbandingan, aljabar awal, dan geometri.',
        ],
        'Ilmu Pengetahuan Alam dan Sosial' => [
            'A' => 'Mengamati organisme, sifat benda, dan lingkungan sekitar melalui kegiatan sederhana.',
            'B' => 'Memahami sistem tubuh, energi, material, dan interaksi sosial dasar.',
            'C' => 'Menganalisis ekosistem, zat dan perubahannya serta fenomena sosial.',
        ],
        'Seni Rupa' => [
            'A' => 'Mengenal elemen seni rupa dan membuat karya sederhana.',
            'B' => 'Membuat karya seni rupa dua dan tiga dimensi serta mengapresiasi karya seni.',
            'C' => 'Menciptakan karya seni rupa bertema dan menganalisis gaya seni rupa.',
        ],
        'Pendidikan Jasmani dan Olahraga' => [
            'A' => 'Melakukan gerak dasar, permainan sederhana, dan kebiasaan hidup sehat.',
            'B' => 'Mempraktikkan variasi gerak, permainan bola, dan kebugaran jasmani.',
            'C' => 'Menganalisis teknik olahraga, aturan permainan, dan menjaga kebugaran jasmani.',
        ],
        'Bahasa Jawa' => [
            'A' => 'Mengenal undhang-undhang, basa krama sederhana, dan unggah-ungguh basa Jawa.',
            'B' => 'Membaca naskah, menulis, dan berbicara basa Jawa sesuai situasi.',
            'C' => 'Mengapresiasi sastra Jawa dan menggunakan basa Jawa secara tepat.',
        ],
        'Bahasa Inggris' => [
            'A' => 'Mengenal greetings, kosakata sederhana, serta listening dan speaking dasar.',
            'B' => 'Memahami kalimat sederhana, membaca teks pendek, dan menulis kalimat sederhana.',
            'C' => 'Membaca dan menulis teks fungsional serta berkomunikasi lisan secara sederhana.',
        ],
        'Batik' => [
            'A' => 'Mengenal ragam motif batik dan proses pembuatan batik sederhana.',
            'B' => 'Membuat karya batik sederhana dan memahami filosofi motif batik.',
            'C' => 'Menciptakan desain batik bertema dan menganalisis nilai budaya batik.',
        ],
    ];
}

function daftar_tp(): array
{
    return [
        "Al-Qur'an" => [
            'A' => ['Menyebutkan nama huruf hijaiyah dengan urutan yang benar', 'Melafalkan bacaan pendek dengan tartil dan tenang', 'Menghafal surah pendek juz \'Am dengan adab membaca'],
            'B' => ['Membaca potongan ayat dengan makhraj huruf yang benar', 'Menerapkan hukum tajwid dasar pada surah pilihan', 'Menghafal surah pendek beserta makna ayatnya'],
            'C' => ['Membaca Al-Qur\'an dengan lancar, tartil, dan tajwid tepat', 'Murottal surah pilihan dengan irama yang baik', 'Menjelaskan makna kandungan ayat yang dibaca'],
        ],
        'Aqidah' => [
            'A' => ['Menyebutkan rukun iman dengan urutan yang benar', 'Menjelaskan sifat wajib Allah secara sederhana', 'Menunjukkan sikap tauhid dalam kegiatan sehari-hari'],
            'B' => ['Menjelaskan rukun Islam beserta pengertiannya', 'Menyebutkan teladan Nabi dan sahabat dalam keimanan', 'Membedakan perbuatan yang mencerminkan keimanan'],
            'C' => ['Menganalisis hubungan akidah dan akhlak dalam kehidupan', 'Membahas isu keimanan di lingkungan digital', 'Menyusun pernyataan iman secara lisan dan tulis'],
        ],
        'Akhlak' => [
            'A' => ['Menyebutkan perilaku terpuji kepada orang tua dan guru', 'Melakukan kebiasaan baik seperti saling menolong', 'Menunjukkan sikap jujur dan disiplin di sekolah'],
            'B' => ['Membedakan akhlak terpuji dan akhlak tercela', 'Menerapkan sopan santun dalam berkomunikasi', 'Menyelesaikan perbedaan dengan cara yang damai'],
            'C' => ['Menganalisis perilaku moral di lingkungan sekitar', 'Menyusun komitmen akhlak pribadi', 'Menunjukkan kepemimpinan moral dalam kelompok'],
        ],
        'Ibadah' => [
            'A' => ['Menyebutkan rukun Islam beserta pengertiannya', 'Mencontohkan tata cara wudu yang benar', 'Melakukan ibadah sehari-hari dengan tertib'],
            'B' => ['Menjalankan shalat wajib berjamaah dengan tertib', 'Menjelaskan hikmah puasa Ramadhan', 'Mempraktikkan zakat fitrah dan sedekah'],
            'C' => ['Menjelaskan hukum ibadah fardu kifayah', 'Menyusun rencana ibadah pribadi satu bulan', 'Menganalisis pengalaman ibadah dalam sejarah'],
        ],
        'Tarikh' => [
            'A' => ['Menyebutkan nama nabi yang wajib diketahui', 'Menceritakan kisah Nabi Muhammad SAW di Makkah', 'Menunjukkan teladan ketaatan para nabi'],
            'B' => ['Menyusun urutan peristiwa Hijrah Nabi ke Madinah', 'Menjelaskan periode Khulafaur Rasyidin', 'Meneladani semangat perjuangan para sahabat'],
            'C' => ['Menganalisis keemasan Islam pada masa Bani Umayyah', 'Menjelaskan sumbangan ilmuwan muslim', 'Membandingkan peradaban Islam masa lalu dan masa kini'],
        ],
        'Kemuhammadiyahan' => [
            'A' => ['Menyebutkan nama pendiri Muhammadiyah', 'Menjelaskan arti lambang Muhammadiyah', 'Menunjukkan kegiatan amal baik di sekolah'],
            'B' => ['Menjelaskan lahirnya gerakan Muhammadiyah oleh KHA Dahlan', 'Menyebutkan amal usaha Muhammadiyah di sekitar', 'Menerapkan nilai kejujuran dan kemandirian dalam kegiatan'],
            'C' => ['Menganalisis kontribusi Muhammadiyah di bidang pendidikan', 'Menyusun program kerja kegiatan dakwah sederhana', 'Mendiskusikan tantangan umat masa kini'],
        ],
        'Bahasa Arab' => [
            'A' => ['Melafalkan huruf dasar bahasa Arab dengan benar', 'Mengucapkan salam dan sapaan sehari-hari', 'Menggunakan kosakata benda di sekitar'],
            'B' => ['Menyusun kalimat sederhana subjek dan predikat', 'Membaca teks pendek dengan makhraj yang tepat', 'Menulis kosakata sesuai tema pembelajaran'],
            'C' => ['Membaca paragraf bahasa Arab dengan tajwid dasar', 'Menulis surat sederhana', 'Menerjemahkan ungkapan sehari-hari'],
        ],
        'Tahsin Al-Qur\'an' => [
            'A' => ['Menyebutkan makhraj setiap huruf hijaiyah', 'Melakukan bacaan mad dasar dengan benar', 'Membaca Iqro bab awal dengan lancar'],
            'B' => ['Menerapkan hukum izhar, idgham, dan iqlab', 'Membaca surah pendek dengan tajwid yang benar', 'Memperbaiki bacaan sesuai koreksi guru'],
            'C' => ['Membaca beberapa halaman dengan tartil', 'Menerapkan kaidah waqaf dan ibtida', 'Menilai bacaan teman berdasarkan kaidah yang benar'],
        ],
        'Tahfidz Al-Qur\'an' => [
            'A' => ['Menghafal Al-Fatihah dengan lancar', 'Menghafal surah pendek juz \'Am', 'Menghafal doa harian dan wirid'],
            'B' => ['Menghafal surah pilihan minimal lima ayat', 'Menjaga hafalan lama melalui muraja\'ah', 'Menampilkan hafalan dengan tartil'],
            'C' => ['Menambah hafalan minimal satu juz per tahun', 'Muraja\'ah hafalan secara berkala', 'Menghubungkan hafalan dengan kehidupan sehari-hari'],
        ],
        'Pendidikan Pancasila' => [
            'A' => ['Menyebutkan Pancasila dan makna sila pertama', 'Menjelaskan identitas diri dan lingkungan', 'Menunjukkan perilaku jujur dan bekerja sama'],
            'B' => ['Menjelaskan hak dan kewajiban siswa', 'Membedakan norma yang berlaku di lingkungan', 'Melakukan musyawarah dalam kegiatan kelas'],
            'C' => ['Menganalisis butir sila pada peristiwa kebangsaan', 'Menjelaskan pasal penting UUD NRI 1945', 'Menyusun gagasan kegiatan bermasyarakat'],
        ],
        'Bahasa Indonesia' => [
            'A' => ['Membaca nyaring teks pendek dengan lancar', 'Menulis huruf dan kata sesuai kaidah', 'Menyimak dan menceritakan isi dongeng'],
            'B' => ['Membaca teks naratif dan menemukan amanat', 'Menulis paragraf deskripsi', 'Menyampaikan pendapat secara lisan'],
            'C' => ['Menganalisis struktur teks fiksi', 'Menulis teks eksposisi sederhana', 'Menyajikan laporan hasil pengamatan'],
        ],
        'Matematika' => [
            'A' => ['Menyelesaikan penjumlahan dan pengurangan hingga 1.000', 'Mengukur panjang dan massa dengan satuan yang tepat', 'Mengenali pola bilangan dan bangun datar'],
            'B' => ['Melakukan operasi bilangan hingga 10.000', 'Membandingkan pecahan sederhana', 'Menyajikan data dalam bentuk diagram'],
            'C' => ['Menyelesaikan operasi bilangan bulat', 'Menyelesaikan masalah perbandingan', 'Menyusun pola menjadi bentuk aljabar'],
        ],
        'Ilmu Pengetahuan Alam dan Sosial' => [
            'A' => ['Mengklasifikasi benda berdasarkan sifatnya', 'Mengamati bagian tumbuhan dan hewan', 'Menjelaskan cara menjaga lingkungan'],
            'B' => ['Menjelaskan fungsi organ tubuh utama', 'Mengidentifikasi sumber energi di sekitar', 'Mengamati perubahan wujud zat'],
            'C' => ['Menganalisis rantai makanan dalam ekosistem', 'Menjelaskan perubahan wujud dan campuran zat', 'Menyajikan hasil observasi sosial'],
        ],
        'Seni Rupa' => [
            'A' => ['Menyebutkan garis, warna, dan bentuk dasar', 'Membuat kolase dari bahan sekitar', 'Menghargai karya teman'],
            'B' => ['Menggambar dengan komposisi yang seimbang', 'Membuat karya kerajinan tiga dimensi', 'Menjelaskan karya seni daerah'],
            'C' => ['Membuat karya dengan tema lingkungan', 'Menerapkan teknik gambar atau media baru', 'Menganalisis aliran seni rupa'],
        ],
        'Pendidikan Jasmani dan Olahraga' => [
            'A' => ['Mempraktikkan gerak lokomotor dan nonlokomotor', 'Bermain permainan kecil beregu', 'Menunjukkan kebiasaan hidup sehat'],
            'B' => ['Mempraktikkan keterampilan manipulatif bola', 'Bermain permainan bola besar sederhana', 'Menilai kebugaran jasmani dasar'],
            'C' => ['Mempraktikkan teknik olahraga pilihan', 'Menjelaskan aturan dan strategi permainan', 'Menyusun program latihan kebugaran'],
        ],
        'Bahasa Jawa' => [
            'A' => ['Mengucapkan salam dan sapaan berbahasa Jawa', 'Membaca cerita anak berbahasa Jawa', 'Menyebutkan unggah-ungguh basa Jawa'],
            'B' => ['Membaca geguritan dengan tepat', 'Menulis cerita pendek berbahasa Jawa', 'Bercakap menggunakan basa krama inggil'],
            'C' => ['Menganalisis unsur intrinsik cerita Jawa', 'Menyusun naskah dramaturgi sederhana', 'Menggunakan basa krama dalam situasi resmi'],
        ],
        'Bahasa Inggris' => [
            'A' => ['Giving greetings and introducing self', 'Recognizing simple classroom vocabulary', 'Responding to simple instructions'],
            'B' => ['Reading simple descriptive text', 'Writing simple sentences', 'Telling a short story orally'],
            'C' => ['Reading functional texts such as invitation and announcement', 'Writing short paragraphs', 'Giving opinion in simple discussion'],
        ],
        'Batik' => [
            'A' => ['Menyebutkan nama motif batik daerah', 'Menggambar motif batik sederhana', 'Menjelaskan alat-alat pembatikan'],
            'B' => ['Membuat pola batik di atas kain', 'Mempraktikkan teknik mewarnai sederhana', 'Menjelaskan filosofi motif batik'],
            'C' => ['Merancang desain batik bertema lokal', 'Memproduksi karya batik secara kelompok', 'Menganalisis nilai budaya dan ekonomi batik'],
        ],
    ];
}
