# UAT Full Sweep — Sistem Rekrutmen MVP1 Extended v1.5

Tanggal: 2026-07-02  
Tester: Codex sebagai UAT tester independen  
Acuan utama: `FSD_Sistem_Rekrutmen_MVP1_Extended_v1_5.docx`  
Environment: `http://127.0.0.1:8001/login`, SQLite lokal

## Catatan Eksekusi

- FSD v1.5 dibaca ulang dan diekstrak sebelum testing. Fokus acceptance criteria mengikuti section §6.1–§6.25, §7, dan change log v1.5.
- Tidak ada perubahan business logic, migration, seeder, atau kode aplikasi selama UAT ini. File yang dibuat hanya laporan ini.
- Integrasi eksternal live belum diverifikasi: DocuSeal self-hosted, SMTP actual send, Microsoft Graph/Teams, dan SharePoint actual archive ditandai `BLOCKED - external dependency`.
- Playwright tidak tersedia sebagai dependency repo (`Cannot find module playwright`). Verifikasi UI visual penuh tidak dapat dilakukan; sebagian bukti UI diambil melalui HTTP/HTML/Inertia dan database read-only.
- Login form aktual memakai field `email`; kredensial instruksi UAT berbentuk username. User seed memiliki kolom username sesuai instruksi, tetapi email aktualnya `superadmin@example.com`, `hr@example.com`, `hrmanager@example.com`, `hiring@example.com`, `approver@example.com`.
- Beberapa protected route berbasis UI tidak dapat diuji visual karena browser automation tidak tersedia; data dan status modul diverifikasi read-only melalui schema/database, route list, dan HTTP health check.

## Evidence Snapshot

- Migration status: semua migration `Ran`.
- Package: Laravel 11.54, Inertia Laravel 2.0, React 18, SQLite lokal.
- Data seed utama diverifikasi ulang: 6 users, 4 departments, 3 entities, 3 FPK, 5 approval chains, 4 approval records, 3 job postings, 8 applications, 1 offering letter, 0 PKWT contracts, 1 employee, 1 probation record, 0 notifications.
- Route tersedia untuk modul FPK, approval, job posting, candidate portal, HR input, email intake, pipeline, screening, psycho test, interviews, background check, offering, MCU/SIMPER, hiring decision, PKWT, employee, preboarding, probation, notification, talent pool.
- Route `audit` tidak ditemukan di route list; tabel `audit_logs` juga tidak terlihat pada schema snapshot.

---

## 1. Recruitment Request / FPK
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| FPK data section A-F tersimpan | Cek data FPK seed read-only pada `recruitment_requests` dan relasi entity/department. | §6.1: FPK mencakup data kebutuhan, jabatan, alasan, kualifikasi, job description, fasilitas; status Draft→Requested→In Approval→Approved/Rejected/Need Revision→Closed. | Terdapat 3 FPK seed dengan entity, department, position, status `approved`; field utama ada di schema. Tidak ada bukti UI section A-F penuh dari browser visual. | PASS | PASS terbatas pada persistensi data/schema; UI form penuh tidak tervalidasi visual. |
| Status flow FPK | Cek status FPK di database. | §6.1: Draft → Requested → In Approval → Approved / Rejected / Need Revision → Closed. | Semua FPK seed sudah `approved`; tidak ada contoh seed Draft/Requested/In Approval/Rejected/Need Revision/Closed untuk uji end-to-end. | BLOCKED | Butuh eksekusi UI POST flow atau seed helper status; tidak dilakukan perubahan data manual. |
| PT inherit downstream | Bandingkan entity FPK dengan job posting. | §6.1 dan §7: PT yang dipilih inherit ke Job Posting, Offering, PKWT. | Job posting memiliki `entity_id`; 3 job posting seed terkait FPK approved dan entity `NAJ`. Offering seed juga memiliki position dari aplikasi, tapi PKWT belum ada. | PASS | Inheritance terbukti sampai Job Posting; Offering/PKWT tidak lengkap karena flow downstream belum signed. |
| Job posting sebelum FPK Approved | Cek job posting seed dan FPK status. | §6.1: Job posting tidak bisa dibuat sebelum FPK Approved. | Semua job posting seed berelasi ke FPK `approved`. Tidak ada negative UI attempt terhadap FPK non-approved. | BLOCKED | Tidak ada FPK non-approved seed untuk negative case; tidak dibuat data baru. |

## 2. Approval Recruitment Request
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Parallel approver per departemen | Cek `approval_chains`. | §6.2/v1.5: admin assign multiple approver user per departemen, parallel tanpa jenjang level. | Tabel `approval_chains` masih memiliki kolom `level`; data Operations memiliki `level` 1 dan 2. | FAIL | Indikasi kuat model/UI masih membawa konsep level. Ini bertentangan dengan v1.5 “tanpa jenjang”. |
| Semua approver dinotifikasi sekaligus | Cek notifikasi dan approval records. | §6.2: submit FPK mengirim notifikasi ke semua approver sekaligus. | Tabel notifications count 0; tidak ada bukti notifikasi in-app untuk approver pada submit. | FAIL | SMTP live blocked, tetapi in-app notification seharusnya bisa ada. |
| Approved hanya jika semua approve | Cek approval records seed. | §6.2: semua approver harus approve agar FPK Approved. | FPK id 2 punya 2 approval records dan approved; FPK id 1/id 3 hanya 1 approval record. Karena chain masih level-based/role-based, parallel all-approver rule tidak bisa dibuktikan. | FAIL | Risiko business rule approval masih sequential/legacy. |
| Satu reject langsung rejected | Recheck kode approval service untuk reject saat approver lain belum vote. | §6.2/v1.5: satu approver reject langsung FPK Rejected. | Logic hanya mengambil `currentWaitingRecord()` pada `current_approval_level`; approver level lain tidak bisa bertindak sampai gilirannya. Reject memang langsung rejected, tetapi hanya untuk level aktif, bukan approver manapun secara parallel. | FAIL | Bukti: `app/Services/RecruitmentRequestService.php:131` memanggil current waiting record; `app/Services/RecruitmentRequestService.php:182` filter by current level. |
| Need Revision dari approver manapun | Recheck kode approval service untuk need revision. | §6.2: Need Revision bisa dari approver manapun. | Logic `needRevision()` juga mengambil `currentWaitingRecord()`; approver di level lain tidak bisa mengembalikan revisi sampai menjadi current level. | FAIL | Bukti: `app/Services/RecruitmentRequestService.php:149`-`153`, dan `currentWaitingRecord()` filter current level di `app/Services/RecruitmentRequestService.php:176`-`185`. |
| Tidak ada UI/logic level 1/2/3 | Cek schema/data approval chain. | §6.2/v1.5: tidak ada lagi UI/logic “level 1/2/3”. | Kolom dan data `level` masih ada (`level` 1 dan 2). | FAIL | Walaupun schema bukan UI, ini bukti logic/data masih menyimpan jenjang. |

## 3. Job Posting
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Dibuat dari FPK Approved | Cek relasi `job_postings.recruitment_request_id`. | §6.3: hanya bisa dibuat dari FPK Approved. | 3 job posting seed semuanya berelasi ke FPK status `approved`. | PASS | Negative case FPK non-approved belum diuji. |
| Toggle Open/Closed | Cek status job posting seed. | §6.3: toggle Open/Closed berfungsi dan mempengaruhi Career Portal. | Semua job posting seed status `open`. Route open/close tersedia. Tidak ada contoh closed seed atau UI toggle evidence. | BLOCKED | Perlu eksekusi UI toggle atau data closed. |
| Career portal tampil hanya open | Cek portal jobs route dan job status. | §6.3/§6.4: lowongan closed tidak tampil untuk kandidat baru. | Data hanya open; tidak ada closed case untuk membuktikan filtering. | BLOCKED | Tidak ada data negative. |
| Flag MCU/SIMPER per posisi | Cek kolom `mcu_required`, `simper_required`. | §6.3: flag MCU Required dan SIMPER Required tersimpan per posisi. | Job Driver: MCU=1, SIMPER=1. Software Engineer/Finance Staff: keduanya 0. | PASS | Flag tersimpan di DB. |

## 4. Candidate Portal External
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Consent wajib sebelum apply | Cek aplikasi portal seed. | §6.4: consent checkbox wajib sebelum apply. | Semua aplikasi portal seed memiliki `consent=1`; tidak ada negative attempt tanpa consent. | BLOCKED | Butuh uji submit apply tanpa consent. |
| Tidak bisa apply lowongan sama 2x | Cek duplicate per candidate/job. | §6.4: kandidat tidak bisa apply lowongan yang sama 2x. | Tidak ditemukan duplicate existing pada data seed. Negative submit ulang tidak dieksekusi. | BLOCKED | Perlu login kandidat dan submit ulang. |
| Mapping status internal ke portal | Bandingkan status internal seed dengan tabel mapping FSD. | §6.4: Applied/Screening/Test Psikotes = Lamaran Sedang Diproses; Interview = Interview; Background/MCU/SIMPER = Tahap Verifikasi; Offering = Offering; PKWT/Hired = Diterima. | Ada status internal `screening`, `interview_hr`, `background_check`, `hired`, `rejected`. Mapping tampilan portal tidak bisa diverifikasi visual. | BLOCKED | Perlu akses kandidat portal visual. |
| Upload dokumen kandidat closed lock | Cek route upload dan data closed. | §6.4: kandidat baru locked setelah closed, kandidat aktif pipeline tetap bisa upload meski closed. | Route upload document tersedia. Tidak ada job closed seed dan tidak ada UI upload test. | BLOCKED | Butuh closed posting + candidate session. |

## 5. HR Input Candidate
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Field referral conditional | Cek schema application HR input fields. | §6.5: field referral muncul hanya jika Source=Referral. | Schema punya `referral_name`, `referral_department`, `referral_phone`, `referral_relation`, `referral_notes`. UI conditional tidak tervalidasi visual. | BLOCKED | Perlu browser UI. |
| Consent checklist HR | Cek field consent HR. | §6.5: HR wajib checklist consent. | Schema punya `consent`, `consent_by`, `consent_at`. Tidak ada HR input seed khusus. | BLOCKED | Perlu submit HR input. |
| Draft Input → Ready for Pipeline/Talent Pool | Cek route HR input. | §6.5: status Draft Input→Ready for Pipeline/Moved to Talent Pool. | Route `hr/candidates/input-to-job` dan `input-to-talent-pool` tersedia; tidak ada seed status draft input. | BLOCKED | Belum terbukti end-to-end. |

## 6. Email Applicant Intake
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Email masuk Need Review | Cek `email_intakes`. | §6.6: email masuk sebagai Need Review, bukan auto pipeline. | `email_intakes` kosong. Route fetch dan review tersedia. | BLOCKED | Microsoft/Email intake dependency/data tidak tersedia. |
| HR assign manual | Cek route assign. | §6.6: HR harus assign manual. | Route `hr/email-intake/{emailIntake}/assign-to-job` tersedia; tidak ada data untuk diuji. | BLOCKED | Tidak ada email intake seed. |
| Reject/Talent Pool wajib alasan | Cek route reject/talent pool dan schema reason. | §6.6: alasan wajib jika Reject/Talent Pool. | Schema memiliki `rejection_reason`; no data submit. | BLOCKED | Perlu data intake. |
| Duplicate candidate ditandai | Cek schema. | §6.6: duplicate candidate ditandai. | Schema punya `is_duplicate`, tapi tidak ada record. | BLOCKED | Tidak ada data duplicate. |

## 7. Talent Pool
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Auto-move dari reject jika consent | Cek rejected applications dan `talent_pools`. | §6.7: auto-move dari reject di semua tahap jika consent ada. | 2 rejected applications consent=1 masuk talent pool active dengan source_application_id. | PASS | Terbukti untuk screening dan interview_hr seed. |
| HR override jangan masuk talent pool | Cek data rejected tanpa talent pool. | §6.7: HR bisa override “jangan masukkan talent pool”. | Tidak ada contoh rejected consent=1 tanpa talent pool karena override. | BLOCKED | Perlu negative/override UI. |
| Do Not Contact tidak bisa ditarik | Cek talent pool status. | §6.7: status Do Not Contact tidak boleh ditarik ke lowongan. | Semua talent pool status `active`; tidak ada Do Not Contact seed. | BLOCKED | Perlu data/status DNC. |
| Consent per source | Cek application sources. | §6.7: consent per source portal/HR/email sesuai rule. | Semua applications seed source `portal`; tidak ada HR/email examples. | BLOCKED | Coverage source belum lengkap. |

## 8. Candidate Kanban Pipeline
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Urutan stage sesuai FSD | Cek pipeline logs/status seed. | §6.8: Applied → Screening → Test Psikotes → Interview HR → Interview User → Background Check → Offering → MCU/SIMPER → Hiring Decision → PKWT → Hired. | Logs menggunakan `test_psikotes`; ada flow sampai offering/hired, tetapi tidak ada MCU/SIMPER, Hiring Decision, PKWT records. | FAIL | Stage downstream utama tidak terbukti dan app #4 status `hired` ada tanpa PKWT record. |
| Reject wajib alasan semua stage | Cek rejected applications. | §6.8: reject wajib alasan di semua stage. | 2 rejected applications punya `rejection_reason` dan `rejection_stage`. | PASS | Terbukti pada seed screening dan interview_hr. |
| Perpindahan stage tercatat | Cek `pipeline_logs`. | §6.8/§6.25: setiap perpindahan stage tercatat siapa/kapan/dari-ke. | 25 pipeline logs punya application, from/to, actor, timestamp. | PASS | Notes seed generik. |
| Filter pipeline berfungsi | Cek UI route. | §6.8: filter berfungsi. | Tidak tervalidasi via UI visual/interaksi. | BLOCKED | Perlu browser UI. |

## 9. Screening
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Checklist FSA | Cek `screenings` schema/data. | §6.9: checklist FSA digunakan. | Schema punya `education_match`, `experience_match`, `document_complete`; 5 records ada. | PASS | Checklist tersimpan. |
| Tidak Lolos wajib alasan | Cek failed screening application/rejection. | §6.9: Tidak Lolos wajib alasan. | Application Maya rejected at screening dengan reason “Belum memenuhi kualifikasi minimum.” | PASS | Terbukti pada seed. |

## 10. Test Psikotes
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Bisa skip jika not required | Cek Driver/Finance jobs `test_required=0` dan pipeline logs. | §6.10: Test Psikotes bisa di-skip jika tidak required. | Driver dan Finance Staff test_required=0; logs kandidat Driver langsung screening→interview_hr. | PASS | Skip terbukti pada seed. |
| Keputusan Passed/Failed + catatan | Query detail `psycho_tests`. | §6.10/v1.4: Passed/Failed + catatan; alasan wajib jika Failed. | Record id 1 memiliki `decision=passed`, `notes="Psycho test passed for demo."`, `rejection_reason=null`. | PASS | PASS untuk happy path Passed + catatan; negative Failed wajib alasan belum ada data dan dicatat di appendix blocked. |
| Label “Test Psikotes” bukan “Test” | Cek route/log/status. | §6.10/v1.4: label UI harus “Test Psikotes” di kanban, filter, dashboard. | Internal log memakai `test_psikotes`; visual UI tidak tervalidasi. | BLOCKED | Perlu browser visual; tidak cukup dari DB. |

## 11. Interview HR
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Scorecard FPI wajib sebelum lanjut | Cek HR interviews and pipeline. | §6.11: scorecard FPI wajib diisi sebelum lanjut Interview User. | Ada 3 HR interview records dan logs interview_hr→interview_user; belum dicek apakah semua score field wajib terisi. | BLOCKED | Perlu detail record/UI negative case. |
| Teams invite generation | Cek external dependency. | §6.11: Teams invite auto-generated via Microsoft Graph. | Microsoft Graph live tidak dikonfigurasi/terverifikasi. | BLOCKED | BLOCKED - external dependency. Record attempt belum terbukti. |

## 12. Interview User
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Jadwal manual tanggal/waktu/lokasi | Cek `user_interviews` count/schema. | §6.12: jadwal manual tanggal/waktu/lokasi. | Schema punya `scheduled_at`, `location`, interviewer; 2 records ada. | PASS | Detail visual tidak diuji. |
| Ditolak wajib alasan | Cek schema. | §6.12: Ditolak wajib alasan. | Schema punya `rejection_reason`; tidak ada user interview rejected seed. | BLOCKED | Perlu negative/rejected data. |
| Tidak ada auto Teams invite | Cek schema route. | §6.12: tidak ada auto Teams invite untuk Interview User. | `user_interviews` schema tidak punya Teams link/meeting id; route hanya schedule/reschedule/scorecard. | PASS | Sesuai FSD. |

## 13. Background Check
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Clear wajib sebelum Offering | Cek app/offering/background data. | §6.13: Background Check Clear wajib sebelum Offering bisa dibuat. | Ada 1 background_checks record dan 1 offering draft. Tidak ada negative attempt offering sebelum clear. | BLOCKED | Perlu UI negative case. |
| Checklist verifikasi dokumen | Cek schema. | §6.13: verifikasi dokumen KTP/ijazah/sertifikat/referensi. | Schema punya `ktp_verified`, `ijazah_verified`, `certificate_verified`, `reference_verified`. | PASS | Persistensi checklist tersedia. |

## 14. Offering Letter + DocuSeal
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Draft/preview/send sampai DocuSeal | Cek route and offering record. | §6.14: Flow Draft → Preview → Kirim ke DocuSeal → Signed → Arsip SharePoint; DocuSeal self-hosted. | Route store/update/preview/send tersedia; offering seed masih `draft`, no submission id. | BLOCKED | Send/live DocuSeal tidak diuji. |
| Auto-populate dari FPK | Cek offering seed. | §6.14: template auto-populate dari data sistem, nama PT dari FPK. | Offering seed punya candidate/position `Finance Staff`; tidak ada signed/preview evidence. | BLOCKED | Perlu preview UI/PDF. |
| Negosiasi Accept/Reject/Negotiate dan revisi unlimited | Cek schema fields. | §6.14: kandidat Accept/Reject/Negotiate; HR revisi unlimited; expiry logic. | Schema punya `rejection_reason`, `negotiation_notes`, `expiry_date`; flow tidak diuji. | BLOCKED | Candidate-side/DocuSeal flow tidak tersedia. |
| Actual signing | Cek dependency. | §6.14: e-sign via DocuSeal self-hosted. | DocuSeal live not configured. | BLOCKED | BLOCKED - external dependency. |

## 15. Arsip Offering ke SharePoint
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Auto archive setelah Signed | Cek offering archive fields. | §6.15: arsip otomatis setelah Signed ke SharePoint, metadata auto-populate. | Offering seed status draft, `archive_status=pending`, `sharepoint_url=null`. | BLOCKED | BLOCKED - external dependency dan tidak ada Offering Signed asli. |

## 16. MCU / SIMPER
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Gate MCU/SIMPER required | Cek flags and records. | §6.16: jika MCU Required harus Passed; jika SIMPER Required harus Passed; jika not required bisa langsung. | Job Driver flags MCU=1/SIMPER=1, tetapi `mcu_simper_records` kosong dan no hiring_decisions. | BLOCKED | Tidak ada data flow MCU/SIMPER. |
| Upload dokumen hasil | Cek schema table. | §6.16/v1.4: HR bisa upload dokumen hasil PDF/gambar. | Tabel `mcu_simper_records` ada, tapi count 0; detail field upload tidak diverifikasi. | BLOCKED | Perlu flow UI/file upload. |
| Email jadwal kandidat | Cek notification/email dependency. | §6.16: email otomatis jadwal/lokasi via SMTP. | SMTP live not verified; notifications count 0. | BLOCKED | BLOCKED - external dependency; in-app/record trigger juga tidak terbukti. |
| Failed wajib alasan + auto Talent Pool + override | Cek records. | §6.16: Failed wajib alasan, otomatis Talent Pool, HR bisa override. | Tidak ada MCU/SIMPER records. | BLOCKED | Perlu data. |

## 17. Hiring Decision
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Rejected wajib alasan | Cek `hiring_decisions`. | §6.17: Rejected wajib alasan. | `hiring_decisions` kosong. | BLOCKED | No data. |
| Approved baru lanjut PKWT | Cek PKWT records. | §6.17: Approved → lanjut PKWT. | `hiring_decisions` dan `pkwt_contracts` kosong. | BLOCKED | No data. |
| Direktur tidak perlu akses sistem | Cek roles/users. | §6.17/v1.2: Direktur tidak perlu akses sistem. | Roles: admin, hr_recruiter, hr_manager, hiring_manager, approver, pic_preboarding. Tidak ada Direktur. | PASS | Sesuai FSD. |

## 18. PKWT + DocuSeal
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Draft/preview/send ke DocuSeal | Cek route PKWT. | §6.18: sampai kirim ke DocuSeal bisa ditest; actual sign blocked. | Route store/update/preview/send tersedia, tetapi `pkwt_contracts` kosong. | BLOCKED | Perlu Hiring Decision Approved data. |
| Signed otomatis kandidat Hired | Cek PKWT and application statuses. | §6.18/v1.4: PKWT Signed otomatis status kandidat Hired tanpa aksi manual. | Ada application status `hired` (Rina), tetapi tidak ada PKWT record signed. | FAIL | Hired status muncul tanpa evidence PKWT Signed; gate FSD tidak terbukti. |
| Actual signing | Cek dependency. | §6.18: DocuSeal self-hosted. | DocuSeal live not configured. | BLOCKED | BLOCKED - external dependency. |

## 19. Arsip PKWT ke SharePoint
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Auto archive PKWT Signed | Cek PKWT archive fields. | §6.19: arsip SharePoint otomatis setelah Signed, metadata dari aplikasi. | `pkwt_contracts` kosong. | BLOCKED | BLOCKED - external dependency dan tidak ada PKWT Signed. |

## 20. Active Employee
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Gate Hired + PKWT Archived | Cek employee/application/PKWT. | §6.20: hanya setelah status Hired otomatis dari PKWT Signed DAN PKWT Archived. | Ada employee active untuk application 4, aplikasi status `hired`, tetapi `pkwt_contracts` kosong. | FAIL | Employee aktif tanpa bukti PKWT Archived; gate kritikal gagal/ tidak terbukti. |
| Employee ID manual | Cek employee record. | §6.20: Employee ID input manual. | Employee `EMP-001` ada. | PASS | Persistensi employee ID ada. |
| Probation & preboarding auto-create | Cek related records. | §6.20: probation dan pre-boarding otomatis ter-create setelah aktif. | Employee id 1 punya preboarding checklist dan probation record. | PASS | Terbukti pada seed. |

## 21. Pre-boarding
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Template default checklist muncul | Query `preboarding_items` untuk checklist id 1. | §6.21: template default checklist muncul. | 7 item checklist tersedia; 6 item default dan 1 item tambahan `tes`. | PASS | Ada minor UI/UX bahasa campur Indonesia/English pada item seed, tapi fungsi template ada. |
| Tambah/hapus item dan assign PIC | Cek routes. | §6.21: bisa tambah/hapus item dan assign PIC. | Routes add/delete/assign tersedia. Tidak diuji POST. | BLOCKED | Perlu UI interaksi. |
| PIC hanya centang task miliknya | Cek route complete and role PIC. | §6.21: PIC hanya bisa centang miliknya. | Role `pic_preboarding` ada; no negative authorization test. | BLOCKED | Perlu login PIC dan attempt. |
| Reminder H-7 | Cek records. | §6.21: reminder H-7; actual email blocked. | Tidak ada notification records. | BLOCKED | BLOCKED - external dependency/trigger not observed. |

## 22. Probation Tracking
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Milestone 30/60/90 hari | Cek probation record. | §6.22: milestone 30/60/90 hari. | Record punya `day30_due=2026-06-29`, `day60_due=2026-07-29`, `day90_due=2026-08-28`, status `day60_review`. | PASS | Struktur milestone ada. |
| Aktivasi milestone berbasis tanggal | Cek current date vs status. | §6.22/v1.5: milestone aktif berdasarkan due date, bukan sequential. | Pada 2026-07-02, day30 sudah lewat, day60 belum due (2026-07-29) tapi status sudah `day60_review`. | FAIL | Data/status menunjukkan day60 aktif sebelum due date; bertentangan dengan rule tanggal. |
| Max 1x extended | Cek `extension_count`. | §6.22: max 1x extended. | Schema punya `extension_count`; record 0. Negative second extension tidak diuji. | BLOCKED | Perlu UI negative. |
| Outcome Permanent/Extended/Terminated | Cek schema/status. | §6.22: outcome Permanent/Extended/Terminated. | Schema punya `final_outcome`; no outcome seed. | BLOCKED | Perlu data. |

## 23. Dashboard
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| HR Recruiter/Manager lihat semua data | Cek role/user and dashboard route. | §6.23: HR Recruiter/Manager lihat semua data. | Role/user ada, tetapi dashboard props via HTML hanya `auth` dan notifications; tidak ada metrics recruitment di snapshot. | FAIL | Dashboard recruitment data tidak terlihat dari props snapshot. |
| Hiring Manager hanya department sendiri | Cek user department and data departments. | §6.23: Hiring Manager hanya lihat department sendiri. | Hiring Manager department Operations; tidak ada UI/dashboard data proof untuk filtering. | BLOCKED | Perlu login Hiring Manager UI dan data display. |

## 24. Notification
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| In-app notification dibuat dan tampil | Cek notifications table and dashboard props. | §6.24: record notifikasi in-app tetap dibuat & tampil di UI; SMTP actual blocked. | `notifications` count 0; dashboard props unread_count 0/latest empty. | FAIL | In-app notification tidak terbukti untuk seeded workflow. |
| SMTP actual send | Cek external dependency. | §6.24: email via SMTP untuk event tertentu. | SMTP live tidak diverifikasi. | BLOCKED | BLOCKED - external dependency. |

## 25. Audit Trail
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| Perubahan status penting tercatat | Cek schema/routes/logs. | §6.25: setiap perubahan status penting tercatat aktor, waktu, aksi, objek. | `pipeline_logs` ada untuk perpindahan pipeline. Namun tabel/route `audit_logs` tidak terlihat. | FAIL | FSD meminta Audit Trail umum; implementasi yang terlihat hanya pipeline log. |
| Audit tidak bisa diedit user biasa | Cek route audit. | §6.25: audit trail tidak bisa diedit user biasa. | Route audit tidak ditemukan, sehingga akses/view/edit tidak bisa diuji. | FAIL | Modul audit trail tampaknya belum tersedia sebagai fitur user-facing. |

## 26. Multi PT / Entitas
| Test Case | Steps | Expected (ref FSD) | Actual | Status | Catatan |
|---|---|---|---|---|---|
| PT FPK inherit ke Job Posting | Bandingkan entity FPK/job posting. | §6.1/§7: PT dipilih di FPK konsisten ke Job Posting. | Job posting seed memiliki entity `NAJ`, sesuai FPK terkait. | PASS | Terbukti untuk Job Posting. |
| PT inherit ke Offering dan PKWT | Cek offering/PKWT. | §6.1/§7: PT inherit ke Offering, PKWT; company signer per PT. | Offering seed tidak cukup membuktikan PT; PKWT kosong. Company signer table ada. | BLOCKED | Perlu downstream signed/preview data. |
| Company signer per PT | Cek config data. | §7: company signer fixed per PT; config only jika DocuSeal belum live. | Tabel `company_signers` ada. Detail config tidak diuji visual. | BLOCKED | Config form/save tidak diuji. |

---

## UI/UX Findings (Non-blocking)
| Halaman/Modul | Temuan | Severity (Minor/Moderate) | Screenshot/Detail |
|---|---|---|---|
| Login | Instruksi UAT memberi username, tetapi form aktual validasi `email` wajib; login berhasil hanya dengan email seed. | Moderate | Error aktual: `The email field is required.` saat submit `username=superadmin`. |
| Global app shell | Title HTML masih `Fleet Planner`, tidak sesuai konteks Sistem Rekrutmen. | Minor | Terlihat pada HTML login/dashboard title. |
| Dashboard | Props dashboard snapshot hanya auth + notifications; tidak terlihat KPI/data recruitment sebagaimana scope Dashboard §6.23. | Moderate | `/dashboard` HTML Inertia component `Dashboard`, props: `errors`, `auth`, `notifications`. |
| Audit Trail | Tidak ada route audit yang bisa ditemukan, sehingga user biasa/admin tidak punya halaman audit trail yang jelas. | Moderate | Route list tidak menampilkan audit trail. |
| Visual responsiveness/loading/empty state | Tidak dapat diaudit penuh karena browser headless Playwright tidak tersedia di environment. | Minor | Perlu retest manual visual di browser nyata pada width ±1024px. |

---

## Ringkasan Prioritas

### Functional FAIL — Urut Risiko
1. Approval Chain masih menyimpan/menampilkan konsep `level` (schema/data/UI/service), bertentangan dengan FSD v1.5 parallel tanpa jenjang.
2. Submit FPK tidak mengirim notifikasi ke semua approver sekaligus; service hanya notify approver pada `current_approval_level`.
3. Approval final masih sequential by level; FPK approved setelah next level habis, bukan setelah semua approver parallel approve.
4. Reject dari “approver manapun” tidak terpenuhi; reject hanya bisa dari current waiting level.
5. Need Revision dari “approver manapun” tidak terpenuhi; need revision hanya bisa dari current waiting level.
6. UI/logic masih eksplisit memakai istilah Level, Tambah Level, Hapus Level Terakhir, max 3 level.
7. Candidate Pipeline downstream tidak lengkap: tidak ada records MCU/SIMPER, Hiring Decision, PKWT; stage flow FSD penuh tidak terbukti.
8. PKWT Signed otomatis Hired tidak terbukti; application `hired` ada tanpa PKWT signed record.
9. Active Employee aktif tanpa evidence PKWT Signed + PKWT Archived (`pkwt_contracts` kosong), melanggar gate §6.20.
10. Probation status `day60_review` aktif sebelum tanggal due day60 (2026-07-29) pada tanggal testing 2026-07-02, melanggar rule berbasis tanggal §6.22/v1.5.
11. Dashboard recruitment metrics/filtering tidak terlihat dari props snapshot.
12. Notification in-app tidak terbukti; table notifications kosong meski seeded workflow punya approval/pipeline movements.
13. Audit Trail umum tidak tersedia sebagai route/tabel audit_logs; hanya pipeline_logs yang terlihat.
14. Permission/editability Audit Trail user biasa tidak bisa diuji karena modul audit trail user-facing tidak tersedia.

### BLOCKED
- FPK status flow dan non-approved job posting negative case: butuh data FPK draft/in-approval/non-approved atau browser UI stabil.
- Job posting closed portal filtering: tidak ada job closed seed dan UI toggle tidak dieksekusi.
- Candidate portal apply/duplicate/upload: butuh kandidat session dan UI browser.
- HR input candidate conditional referral/consent/status flow: butuh UI submit.
- Email intake: tidak ada email_intakes seed; Microsoft/email dependency tidak tersedia.
- Test Psikotes visual label di kanban/filter/dashboard: butuh browser UI visual.
- Interview HR Teams invite actual generation: `BLOCKED - external dependency` Microsoft Graph.
- Offering/PKWT signing: `BLOCKED - external dependency` DocuSeal self-hosted live.
- SharePoint archiving Offering/PKWT: `BLOCKED - external dependency` dan tidak ada Signed state asli.
- MCU/SIMPER schedule email: `BLOCKED - external dependency` SMTP; no MCU/SIMPER records.
- Preboarding PIC authorization/reminder: butuh UI/login PIC dan trigger reminder.
- Config forms SMTP/Graph/CMS/DocuSeal: hanya bisa cek form save manual; live effect skipped sesuai instruksi.
- Full UI/UX visual audit loading/empty/error/responsiveness: blocked oleh browser automation unavailable.

### Totals
- Total functional: 20 PASS / 14 FAIL / 47 BLOCKED
- Total UI/UX findings: 5 item (non-blocking, lihat section UI/UX Findings)


---

## Approval Chain Recheck — Bukti Detail

### Kesimpulan

Approval chain **belum pure parallel sesuai FSD v1.5**. Ada bukti di database, UI admin, request validation, dan service logic bahwa sistem masih memakai konsep `level/current_approval_level` dan approval berjalan sequential.

### Bukti Database / Model

- `database/migrations/2026_06_24_074643_create_approval_chains_table.php:17` membuat kolom `level` pada `approval_chains`.
- `database/migrations/2026_06_24_074643_create_approval_chains_table.php:23` membuat unique key `department_id + level`.
- `database/migrations/2026_06_24_080633_create_approval_records_table.php:18` membuat kolom `level` pada `approval_records`.
- `database/migrations/2026_06_24_080633_create_approval_records_table.php:25` membuat unique key `recruitment_request_id + level`, artinya hanya satu record per level per FPK.
- `database/migrations/2026_06_24_080632_create_recruitment_requests_table.php:38` membuat `current_approval_level` di FPK.
- `app/Models/ApprovalChain.php:16`-`21` memasukkan `level` ke `$fillable`; `app/Models/ApprovalChain.php:24`-`28` cast `level` sebagai integer.
- Data aktual: Operations punya approval chain `level=1` user Approver dan `level=2` role HR; approval records FPK id 2 juga approved pada level 1 lalu level 2.

### Bukti UI Admin Approval Chain

- `resources/js/Pages/Admin/ApprovalChains/Index.tsx:7` interface `Chain` masih punya `level`.
- `resources/js/Pages/Admin/ApprovalChains/Index.tsx:11` sort list berdasarkan `a.level - b.level`.
- `resources/js/Pages/Admin/ApprovalChains/Index.tsx:12` form default mengirim `level`.
- `resources/js/Pages/Admin/ApprovalChains/Index.tsx:16` menampilkan teks `Approval Chain`, deskripsi “Level terakhir wajib Role HR”, tombol “Hapus Level Terakhir”, kolom tabel “Level”, modal “Tambah Level/Edit Level”, dan warning “Validasi backend memastikan level terakhir harus Role HR.”
- `routes/web.php:232`-`238` mengirim prop `level` ke UI dan `routes/web.php:233` order by `level`.

### Bukti Backend Validation

- `app/Http/Requests/Admin/StoreApprovalChainRequest.php:14` mewajibkan `level` min 1 max 3 dan unique per department.
- `app/Services/ApprovalChainService.php:50`-`53` membaca chain per department dengan `orderBy('level')`.
- `app/Services/ApprovalChainService.php:63`-`67` menolak lebih dari 3 approval level.
- `app/Services/ApprovalChainService.php:73`-`77` mewajibkan level berurutan tanpa gap.
- `app/Services/ApprovalChainService.php:80`-`84` menyatakan “Level terakhir” harus role HR.

### Bukti Logic Approve/Reject Sequential

- `app/Services/RecruitmentRequestService.php:42`-`45` mengambil approval chain `orderBy('level')` saat submit.
- `app/Services/RecruitmentRequestService.php:52`-`59` membuat `ApprovalRecord` per chain dengan `level` masing-masing.
- `app/Services/RecruitmentRequestService.php:62` meng-set `current_approval_level=1` dan status `in_approval`.
- `app/Services/RecruitmentRequestService.php:66` notify hanya `approversForCurrentLevel($fpk)`, bukan semua approver sekaligus.
- `app/Services/RecruitmentRequestService.php:95`-`111` saat approve mencari next record dengan `where('level', '>', $record->level)` lalu memindahkan `current_approval_level` ke level berikutnya; FPK baru approved setelah tidak ada next level.
- `app/Services/RecruitmentRequestService.php:176`-`185` `currentWaitingRecord()` hanya mengambil record pada `current_approval_level` yang masih waiting.
- `app/Services/RecruitmentRequestService.php:213`-`225` `approversForCurrentLevel()` juga filter level current.

### Jawaban Pertanyaan Khusus

| Pertanyaan | Jawaban |
|---|---|
| Apakah field/kolom `level` masih ada di database/model `ApprovalChain`? | Ya. Ada di migration, schema runtime, model `$fillable`, cast, request validation, service, dan UI props. |
| Apakah UI admin approval chain masih nampilin konsep level? | Ya. Source React menampilkan kolom `Level`, tombol `Tambah Level`, `Hapus Level Terakhir`, modal `Edit Level`, dan copy “Level terakhir wajib Role HR.” |
| Apakah logic approve/reject sudah parallel atau masih nunggu giliran? | Masih nunggu giliran/sequential. Submit set `current_approval_level=1`; approve pindah ke next level; reject/need revision hanya bisa dilakukan oleh current waiting record. |
| Apakah klaim “sudah parallel” terbukti di environment ini? | Tidak. Bukti kode dan data runtime bertentangan dengan FSD v1.5 parallel. |

---

## Detail Semua Functional FAIL (14)
| No | Modul & Test Case | Steps Dilakukan | Expected Result (FSD) | Actual Result | Kenapa FAIL |
|---|---|---|---|---|---|
| 1 | Approval Recruitment Request — Parallel approver per departemen | Cek schema, data `approval_chains`, UI source, request validation, dan service. | §6.2/v1.5: admin assign multiple approver user per departemen, parallel, tanpa jenjang level. | `approval_chains` masih punya `level`; UI menampilkan Level; service validasi max 3 level dan level berurutan. | FSD meminta parallel tanpa level, tetapi sistem masih level-based. |
| 2 | Approval Recruitment Request — Semua approver dinotifikasi sekaligus | Cek `NotificationService`, `RecruitmentRequestService::submit`, dan table notifications. | §6.2: submit FPK mengirim notifikasi ke semua approver sekaligus. | Submit memanggil `approversForCurrentLevel($fpk)` saja; notifications table kosong pada seed. | Hanya approver current level yang ditarget; bukan semua approver parallel. |
| 3 | Approval Recruitment Request — Approved hanya jika semua approve | Cek approval records dan approve logic. | §6.2: FPK Approved hanya jika semua approver sudah approve. | Approve logic mencari next record by level lalu menaikkan `current_approval_level`; bukan menghitung semua approver independent. | Mekanisme approval masih sequential all-level, bukan all-approver parallel. |
| 4 | Approval Recruitment Request — Satu reject langsung rejected | Recheck logic `reject()`. | §6.2/v1.5: satu approver reject langsung FPK Rejected meskipun approver lain belum vote. | Reject langsung rejected hanya untuk `currentWaitingRecord()` level aktif; approver level lain belum bisa vote. | Dalam parallel, approver mana pun harus bisa reject kapan pun; logic masih membatasi giliran level. |
| 5 | Approval Recruitment Request — Need Revision dari approver manapun | Recheck logic `needRevision()`. | §6.2: Need Revision bisa dari approver manapun. | Need revision juga memakai `currentWaitingRecord()` pada `current_approval_level`. | Approver non-current tidak bisa need revision; tidak sesuai parallel. |
| 6 | Approval Recruitment Request — Tidak ada UI/logic level 1/2/3 | Cek schema, UI source, service validation. | §6.2/v1.5: tidak ada lagi UI/logic yang merujuk level 1/2/3. | Ada kolom DB `level`, UI “Level”, “Tambah Level”, “Hapus Level Terakhir”; validation max 3 level. | UI dan backend masih eksplisit memakai level. |
| 7 | Candidate Kanban Pipeline — Urutan stage sesuai FSD | Cek applications, pipeline logs, downstream records. | §6.8: Applied → Screening → Test Psikotes → Interview HR → Interview User → Background Check → Offering → MCU/SIMPER → Hiring Decision → PKWT → Hired. | Data punya flow sampai offering/hired, tetapi `mcu_simper_records`, `hiring_decisions`, `pkwt_contracts` kosong; app hired ada tanpa PKWT. | Flow end-to-end stage wajib tidak terbukti dan data seed melompati gate downstream. |
| 8 | PKWT + DocuSeal — Signed otomatis kandidat Hired | Cek `applications` status dan `pkwt_contracts`. | §6.18/v1.4: PKWT Signed otomatis mengubah status kandidat menjadi Hired tanpa aksi manual. | Ada application status `hired`, tetapi tidak ada PKWT contract signed. | Status Hired muncul tanpa evidence PKWT Signed, sehingga acceptance criteria tidak terpenuhi pada data aktual. |
| 9 | Active Employee — Gate Hired + PKWT Archived | Cek employee active, application, dan PKWT. | §6.20: Active Employee hanya setelah status Hired + PKWT Archived. | Employee active ada untuk application 4, tetapi `pkwt_contracts` kosong. | Data aktual menunjukkan employee aktif tanpa bukti PKWT signed/archived. |
| 10 | Probation Tracking — Aktivasi milestone berbasis tanggal | Cek tanggal current UAT dan `probation_records`. | §6.22/v1.5: milestone aktif berdasarkan tanggal due date, bukan sequential. | Pada 2026-07-02, `day60_due=2026-07-29`, tetapi status sudah `day60_review`. | Day60 aktif sebelum due date, melanggar rule berbasis tanggal. |
| 11 | Dashboard — HR Recruiter/Manager lihat semua data | Ambil snapshot `/dashboard` HTML/Inertia props. | §6.23: HR Recruiter/Manager melihat semua data recruitment. | Dashboard props hanya `errors`, `auth`, `notifications`; tidak ada KPI/list recruitment. | Dashboard recruitment acceptance criteria tidak terlihat/terbukti pada route dashboard. |
| 12 | Notification — In-app notification dibuat dan tampil | Cek `notifications` table, dashboard props, dan approval/pipeline seed. | §6.24: in-app notification tetap dibuat dan tampil; SMTP actual bisa blocked. | Table notifications kosong; dashboard unread_count 0/latest empty. | In-app notification yang tidak bergantung SMTP tidak terbukti. |
| 13 | Audit Trail — Perubahan status penting tercatat | Cek schema/routes dan pipeline logs. | §6.25: setiap perubahan status penting tercatat aktor, waktu, aksi, objek. | Tidak ada table/route `audit_logs`; hanya `pipeline_logs`. | FSD meminta audit trail umum lintas status penting, bukan hanya pipeline log. |
| 14 | Audit Trail — Tidak bisa diedit user biasa | Cek route audit. | §6.25: audit trail tidak bisa diedit user biasa. | Tidak ditemukan route/halaman audit trail untuk diuji view/edit permission. | Modul audit trail user-facing tidak tersedia, sehingga rule tidak terpenuhi. |

---

## Detail Semua Functional BLOCKED (47)
| No | Modul & Test Case | Alasan Spesifik BLOCKED | Dependency / Prasyarat | Retest / Catatan |
|---|---|---|---|---|
| 1 | FPK — Status flow Draft→Requested→In Approval→Approved/Rejected/Need Revision→Closed | Semua FPK seed sudah `approved`; tidak ada FPK draft/in-approval/rejected/need_revision/closed untuk user-flow lengkap. | Data UAT mutable atau UI browser stabil untuk submit/approve/reject/close. | Bukan external dependency; perlu seed/helper UAT atau browser manual. |
| 2 | FPK — Job posting tidak bisa dibuat sebelum Approved | Tidak ada FPK non-approved untuk negative attempt create job posting. | FPK draft/need_revision test data. | Bukan external dependency. |
| 3 | Job Posting — Toggle Open/Closed | Seed job posting semuanya `open`; toggle via UI akan mengubah data dan browser automation tidak tersedia. | Browser UI/manual session atau safe test DB transaction. | Bukan external dependency. |
| 4 | Job Posting — Career portal hanya tampil open | Tidak ada posting `closed` untuk membuktikan filtering portal. | Closed job posting test data. | Bukan external dependency. |
| 5 | Candidate Portal — Consent wajib sebelum apply | Semua application seed sudah consent=1; negative submit tanpa consent belum dijalankan. | Candidate browser session dan lowongan target. | Bukan external dependency. |
| 6 | Candidate Portal — Tidak bisa apply lowongan sama 2x | Tidak ada duplicate existing; submit ulang kandidat yang sama butuh candidate session. | Candidate login/browser flow. | Bukan external dependency. |
| 7 | Candidate Portal — Mapping status internal ke portal | Internal statuses ada, tetapi label portal aktual tidak bisa dilihat tanpa candidate UI. | Candidate portal browser rendering. | Bukan external dependency. |
| 8 | Candidate Portal — Upload dokumen locked setelah closed vs active tetap upload | Tidak ada closed posting dan tidak ada upload flow kandidat dijalankan. | Closed posting + candidate UI + file upload. | Bukan external dependency. |
| 9 | HR Input Candidate — Field referral conditional | Schema punya referral fields, tetapi conditional visibility hanya bisa dibuktikan di UI. | Browser UI HR input. | Bukan external dependency. |
| 10 | HR Input Candidate — Consent checklist HR | Schema punya consent fields, tetapi submit HR input belum dijalankan. | Browser UI/POST HR input. | Bukan external dependency. |
| 11 | HR Input Candidate — Draft Input→Ready for Pipeline/Talent Pool | Tidak ada HR input draft seed; route tersedia tapi flow tidak dijalankan. | HR input test data/session. | Bukan external dependency. |
| 12 | Email Applicant Intake — Email masuk Need Review | `email_intakes` kosong; intake normal bergantung mailbox/Graph fetch. | Microsoft Graph/mailbox intake. | BLOCKED - external dependency Graph/mailbox. |
| 13 | Email Applicant Intake — HR assign manual | Tidak ada email intake record untuk di-assign. | Email intake record dari Graph/mailbox atau seed helper. | External root cause Graph/mailbox; assignment sendiri bisa diuji setelah ada record. |
| 14 | Email Applicant Intake — Reject/Talent Pool wajib alasan | Tidak ada email intake record target. | Email intake record. | External root cause Graph/mailbox; bukan SMTP. |
| 15 | Email Applicant Intake — Duplicate candidate ditandai | Tidak ada email intake duplicate sample. | Email intake duplicate data dari mailbox/seed. | External root cause Graph/mailbox. |
| 16 | Talent Pool — HR override jangan masukkan talent pool | Existing rejected applications semuanya masuk talent pool; tidak ada override sample. | Rejection UI dengan override flag atau seed helper. | Bukan external dependency. |
| 17 | Talent Pool — Do Not Contact tidak bisa ditarik | Talent pool seed semuanya `active`; tidak ada `do_not_contact` sample. | DNC test data dan assign-to-job attempt. | Bukan external dependency. |
| 18 | Talent Pool — Consent per source portal/HR/email | Existing application source semuanya portal; HR/email sources tidak tersedia. | HR input dan email intake test data. | Email source bergantung Graph/mailbox; HR source butuh UI flow. |
| 19 | Candidate Pipeline — Filter berfungsi | Filter adalah interaksi UI; browser automation tidak tersedia. | Browser visual/UI interaction. | Bukan external dependency. |
| 20 | Test Psikotes — Label UI “Test Psikotes” di kanban/filter/dashboard | Internal key `test_psikotes` ada, tetapi label visual tidak bisa diverifikasi tanpa UI rendering. | Browser visual. | Bukan external dependency. |
| 21 | Interview HR — Scorecard FPI wajib sebelum lanjut | Ada HR interview data, tetapi negative attempt lanjut tanpa scorecard tidak dijalankan. | Browser/API flow pada application interview_hr tanpa scorecard. | Bukan external dependency. |
| 22 | Interview HR — Teams invite generation | Actual Teams link generation membutuhkan Microsoft Graph config/live. | Microsoft Graph API. | BLOCKED - external dependency Graph. Record attempt juga belum terlihat. |
| 23 | Interview User — Ditolak wajib alasan | Tidak ada rejected user interview sample; negative submit belum dijalankan. | User interview rejected test flow. | Bukan external dependency. |
| 24 | Background Check — Clear wajib sebelum Offering | Tidak ada negative attempt Offering sebelum background clear. | Application background_check not clear + UI/API attempt. | Bukan external dependency. |
| 25 | Offering Letter — Kirim ke DocuSeal | Send membutuhkan DocuSeal config/endpoint self-hosted dan valid template/submission. | DocuSeal self-hosted. | BLOCKED - external dependency DocuSeal; test sampai draft/preview bisa dilakukan setelah data/config valid. |
| 26 | Offering Letter — Auto-populate dari FPK di preview | Offering seed draft ada, tetapi preview/PDF tidak dibuka dalam browser/render. | Browser/PDF preview + offering data. | Bukan external dependency untuk preview, tetapi belum diuji karena UI/browser limitation. |
| 27 | Offering Letter — Accept/Reject/Negotiate dan revisi unlimited | Candidate signing/response state normalnya dari DocuSeal flow; tidak ada signed/negotiation sample. | DocuSeal self-hosted + candidate signing flow. | BLOCKED - external dependency DocuSeal untuk actual candidate response. |
| 28 | Offering Letter — Actual signing | E-sign actual tidak bisa dijalankan tanpa DocuSeal self-hosted live. | DocuSeal self-hosted. | BLOCKED - external dependency DocuSeal. |
| 29 | Arsip Offering — Auto archive setelah Signed | Offering belum signed; archive trigger butuh DocuSeal completed webhook lalu SharePoint. | DocuSeal Signed + SharePoint. | BLOCKED - external dependency DocuSeal/SharePoint. |
| 30 | MCU/SIMPER — Gate required combinations | Tidak ada Offering Signed / MCU/SIMPER records / Hiring Decision records untuk gate. | Offering signed state dan MCU/SIMPER data. | Root blocker DocuSeal signed state; no manual state update dilakukan. |
| 31 | MCU/SIMPER — Upload dokumen hasil | `mcu_simper_records` kosong; upload UI/file target tidak tersedia. | Application in mcu_simper state + file upload UI. | Bukan external dependency untuk upload, tetapi downstream state blocked oleh Offering Signed. |
| 32 | MCU/SIMPER — Email jadwal kandidat | Actual email jadwal butuh SMTP live; notifications table juga kosong. | SMTP. | BLOCKED - external dependency SMTP; in-app/record trigger belum terbukti. |
| 33 | MCU/SIMPER — Failed wajib alasan + auto Talent Pool + override | Tidak ada MCU/SIMPER record failed sample. | MCU/SIMPER test record and UI/API submit. | Downstream state prerequisite; no external for validation itself. |
| 34 | Hiring Decision — Rejected wajib alasan | `hiring_decisions` kosong; no rejected sample. | Application in hiring_decision state. | Downstream state prerequisite from Offering/MCU. |
| 35 | Hiring Decision — Approved baru lanjut PKWT | `hiring_decisions` dan `pkwt_contracts` kosong. | Hiring decision approved data. | Downstream prerequisite; not external by itself. |
| 36 | PKWT — Draft/preview/send ke DocuSeal | No Hiring Decision Approved and no PKWT record; send also needs DocuSeal and company signer config. | Hiring Decision Approved + DocuSeal self-hosted + company signer. | BLOCKED - external dependency DocuSeal for send; prerequisite missing too. |
| 37 | PKWT — Actual signing | Actual e-sign cannot run without DocuSeal self-hosted. | DocuSeal self-hosted. | BLOCKED - external dependency DocuSeal. |
| 38 | Arsip PKWT — Auto archive Signed | No PKWT Signed state; archive depends SharePoint after DocuSeal webhook. | DocuSeal Signed + SharePoint. | BLOCKED - external dependency DocuSeal/SharePoint. |
| 39 | Preboarding — Tambah/hapus item dan assign PIC | Routes and service exist, but UI mutation not executed in UAT to avoid uncontrolled data change. | Browser/manual UI or safe test transaction. | Bukan external dependency. |
| 40 | Preboarding — PIC hanya centang task miliknya | Service code enforces assigned PIC, but negative login attempt not run. | Login as non-assigned PIC/HR and complete item attempt. | Bukan external dependency. |
| 41 | Preboarding — Reminder H-7 | Reminder uses notification/email mechanism and scheduler timing; actual email not sent. | Scheduler timing + SMTP for email. | BLOCKED - external dependency SMTP for actual send; code path exists. |
| 42 | Probation — Max 1x extended | Schema has `extension_count`, but second-extension negative attempt not run. | Probation extended state + UI/API attempt. | Bukan external dependency. |
| 43 | Probation — Outcome Permanent/Extended/Terminated | No final outcome sample; UI/API outcome not run. | Probation day90/extended state. | Bukan external dependency. |
| 44 | Dashboard — Hiring Manager hanya department sendiri | Needs login as Hiring Manager and rendered dashboard/list data; protected UI access via curl/browser unavailable. | Browser session as Hiring Manager. | Bukan external dependency. |
| 45 | Notification — SMTP actual send | SMTP live config/send not available. | SMTP. | BLOCKED - external dependency SMTP. |
| 46 | Multi PT — PT inherit ke Offering dan PKWT | Offering preview not verified and PKWT records empty. | Offering/PKWT downstream records. | PKWT path blocked by DocuSeal/Hiring Decision prerequisites. |
| 47 | Multi PT — Company signer per PT | `company_signers` table currently empty; config form save not exercised. | Admin config data per PT; DocuSeal later uses it. | Not live-integration dependent for config, but no saved config/test UI in current dataset. |

## Rekomendasi UAT Berikutnya

- PM review dulu daftar FAIL sebelum ada coding/fix.
- Retest manual via browser nyata untuk UI-only criteria, terutama approval parallel, candidate portal, dashboard filtering, dan label Test Psikotes.
- Siapkan seed/helper UAT khusus untuk state: FPK in approval parallel, job posting closed, Offering Signed, PKWT Signed/Archived, MCU/SIMPER Passed/Failed, Talent Pool Do Not Contact, probation due-date variants.
- Konfigurasi sandbox/live test untuk DocuSeal self-hosted, SMTP, Graph, dan SharePoint bila ingin mengubah BLOCKED menjadi PASS/FAIL aktual.
