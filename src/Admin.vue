<template>
  <main class="admin-page">
    <div class="admin-content">
      <section class="admin-heading">
        <div>
          <p class="eyebrow">ADMIN / LEAVE REVIEW</p>
          <h1>พิจารณาคำขอลางาน</h1>
          <p>ตรวจสอบรายละเอียดและบันทึกผลการพิจารณาคำขอของพนักงาน</p>
        </div>
        <button class="refresh-button" type="button" :disabled="loading" @click="loadRequests">
          {{ loading ? 'กำลังโหลด...' : '↻ รีเฟรช' }}
        </button>
      </section>

      <section class="stats-row" aria-label="สรุปคำขอ">
        <article class="stat-card"><span>รอพิจารณา</span><strong>{{ countByStatus('pending') }}</strong></article>
        <article class="stat-card"><span>อนุมัติแล้ว</span><strong>{{ countByStatus('approved') }}</strong></article>
        <article class="stat-card"><span>ไม่อนุมัติ</span><strong>{{ countByStatus('rejected') }}</strong></article>
      </section>

      <section class="requests-panel">
        <div class="panel-toolbar">
          <h2>รายการคำขอ <span>{{ filteredRequests.length }}</span></h2>
          <label class="filter-label" for="status-filter">แสดง
            <select id="status-filter" v-model="filter">
              <option value="all">ทั้งหมด</option>
              <option value="pending">รอพิจารณา</option>
              <option value="approved">อนุมัติแล้ว</option>
              <option value="rejected">ไม่อนุมัติ</option>
            </select>
          </label>
        </div>

        <p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
        <p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p>

        <div v-if="loading && !requests.length" class="empty-state">กำลังโหลดรายการคำขอ...</div>
        <div v-else-if="!filteredRequests.length" class="empty-state">
          <span class="empty-icon">✓</span>
          <strong>ไม่มีคำขอในหมวดนี้</strong>
          <p>รายการที่ส่งเข้ามาจะแสดงที่นี่</p>
        </div>
        <div v-else class="request-list">
          <article v-for="request in filteredRequests" :key="request.id" class="request-card">
            <div class="request-main">
              <div class="request-title-row">
                <h3>{{ request.type }}</h3>
                <span class="status-pill" :class="request.status">{{ statusLabel(request.status) }}</span>
              </div>
              <p class="employee">{{ request.userName || 'พนักงาน' }} <span>{{ request.email }}</span></p>
              <div class="request-details">
                <span><b>ช่วงวันลา</b>{{ formatDate(request.startDate) }} — {{ formatDate(request.endDate) }} ({{ request.days }} วัน)</span>
                <span><b>ส่งคำขอ</b>{{ formatDateTime(request.createdAt) }}</span>
              </div>
              <p class="reason"><b>เหตุผล</b>{{ request.reason }}</p>
              <button v-if="request.hasEvidence" class="evidence-button" type="button" @click="viewEvidence(request)">ดูภาพหลักฐาน</button>
            </div>
            <div v-if="request.status === 'pending'" class="actions">
              <button class="reject-button" type="button" :disabled="processingId === request.id" @click="review(request, 'rejected')">ไม่อนุมัติ</button>
              <button class="approve-button" type="button" :disabled="processingId === request.id" @click="review(request, 'approved')">อนุมัติ</button>
            </div>
          </article>
        </div>
      </section>

      <div v-if="evidenceImage" class="evidence-modal" role="dialog" aria-modal="true" aria-label="ภาพหลักฐาน" @click.self="evidenceImage = ''">
        <div class="evidence-dialog">
          <button class="close-modal" type="button" aria-label="ปิด" @click="evidenceImage = ''">×</button>
          <h2>ภาพหลักฐาน</h2>
          <img :src="evidenceImage" alt="ภาพหลักฐานประกอบคำขอลา" />
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

const API_URL = 'http://localhost/new_project_68701996/php/dayoff.php?action=admin'
const requests = ref([])
const filter = ref('pending')
const loading = ref(false)
const processingId = ref(null)
const errorMessage = ref('')
const successMessage = ref('')
const evidenceImage = ref('')
const filteredRequests = computed(() => filter.value === 'all'
  ? requests.value
  : requests.value.filter(request => request.status === filter.value))

onMounted(loadRequests)

function countByStatus(status) {
  return requests.value.filter(request => request.status === status).length
}

function statusLabel(status) {
  return { pending: 'รอพิจารณา', approved: 'อนุมัติแล้ว', rejected: 'ไม่อนุมัติ' }[status] || status
}

function formatDate(value) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('th-TH', { day: 'numeric', month: 'short', year: 'numeric' })
    .format(new Date(`${value.slice(0, 10)}T00:00:00`))
}

function formatDateTime(value) {
  if (!value) return '—'
  const normalized = value.replace(' ', 'T')
  return new Intl.DateTimeFormat('th-TH', {
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
  }).format(new Date(normalized))
}

async function loadRequests() {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await fetch(API_URL, { credentials: 'include' })
    const result = await response.json()
    if (!response.ok || !result.success) throw new Error(result.message || 'โหลดคำขอไม่สำเร็จ')
    requests.value = result.requests
  } catch (error) {
    errorMessage.value = error instanceof TypeError
      ? 'เชื่อมต่อระบบไม่ได้ ตรวจสอบว่า Apache และ MySQL ทำงานอยู่'
      : error.message
  } finally {
    loading.value = false
  }
}

async function review(request, status) {
  processingId.value = request.id
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: request.id, status }),
    })
    const result = await response.json()
    if (!response.ok || !result.success) throw new Error(result.message || 'บันทึกผลไม่สำเร็จ')
    successMessage.value = result.message
    await loadRequests()
  } catch (error) {
    errorMessage.value = error instanceof TypeError ? 'เชื่อมต่อระบบไม่ได้' : error.message
  } finally {
    processingId.value = null
  }
}

async function viewEvidence(request) {
  errorMessage.value = ''
  try {
    const response = await fetch(`${API_URL}&evidence=${encodeURIComponent(request.id)}`, { credentials: 'include' })
    const result = await response.json()
    if (!response.ok || !result.success) throw new Error(result.message || 'ไม่สามารถเปิดภาพหลักฐานได้')
    evidenceImage.value = `data:${result.mime};base64,${result.image}`
  } catch (error) {
    errorMessage.value = error instanceof TypeError ? 'เชื่อมต่อระบบไม่ได้' : error.message
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }
.admin-page { min-height: calc(100vh - 66px); padding: 38px 24px 60px; color: #292a31; background: #f7f8fb; font-family: 'DM Sans', 'Noto Sans Thai', Arial, sans-serif; }
.admin-content { width: min(100%, 1080px); margin: 0 auto; }
.admin-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 25px; }
.eyebrow { margin: 0 0 9px; color: #9296a4; font-size: 10px; font-weight: 700; letter-spacing: .12em; }
.admin-heading h1 { margin: 0; font-size: clamp(24px, 4vw, 30px); letter-spacing: -.035em; }
.admin-heading p:not(.eyebrow) { margin: 9px 0 0; color: #858a98; font-size: 12px; }
.refresh-button { padding: 10px 14px; border: 1px solid #e3e5eb; border-radius: 7px; color: #565b69; background: #fff; font: inherit; font-size: 11px; cursor: pointer; }
.refresh-button:disabled { opacity: .6; cursor: wait; }
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px; }
.stat-card { display: flex; min-height: 84px; align-items: center; justify-content: space-between; padding: 18px 20px; border: 1px solid #eceef3; border-radius: 10px; background: #fff; }
.stat-card span { color: #777c8a; font-size: 11px; }
.stat-card strong { color: #292a31; font-size: 24px; }
.requests-panel { padding: 22px; border: 1px solid #eceef3; border-radius: 11px; background: #fff; }
.panel-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 17px; }
.panel-toolbar h2 { margin: 0; font-size: 15px; }
.panel-toolbar h2 span { margin-left: 5px; color: #989ca8; font-size: 11px; font-weight: 500; }
.filter-label { display: flex; align-items: center; gap: 8px; color: #858a98; font-size: 10px; }
.filter-label select { min-height: 34px; padding: 0 8px; border: 1px solid #e5e7ed; border-radius: 6px; color: #515562; background: #fff; font: inherit; font-size: 10px; }
.request-list { display: grid; gap: 12px; }
.request-card { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 17px; border: 1px solid #eceef3; border-radius: 9px; }
.request-main { min-width: 0; flex: 1; }
.request-title-row { display: flex; align-items: center; gap: 10px; }
.request-title-row h3 { margin: 0; font-size: 13px; }
.status-pill { padding: 5px 8px; border-radius: 20px; font-size: 9px; white-space: nowrap; }
.status-pill.pending { color: #9c742c; background: #fff5df; }
.status-pill.approved { color: #318263; background: #e8f7f0; }
.status-pill.rejected { color: #b94a57; background: #fff0f1; }
.employee { margin: 8px 0 12px; color: #555a67; font-size: 11px; font-weight: 600; }
.employee span { margin-left: 8px; color: #9296a3; font-size: 10px; font-weight: 400; }
.request-details { display: flex; flex-wrap: wrap; gap: 9px 24px; color: #666b78; font-size: 10px; }
.request-details span { display: flex; flex-wrap: wrap; gap: 8px; }
.request-details b, .reason b { color: #9296a3; font-weight: 500; }
.reason { margin: 12px 0 0; color: #626775; font-size: 10px; line-height: 1.65; overflow-wrap: anywhere; }
.reason b { margin-right: 8px; }
.evidence-button { margin-top: 11px; padding: 0; border: 0; color: #555b87; background: transparent; font: inherit; font-size: 10px; text-decoration: underline; cursor: pointer; }
.actions { display: flex; flex: 0 0 auto; gap: 8px; }
.actions button { min-height: 36px; padding: 0 11px; border: 1px solid transparent; border-radius: 6px; font: inherit; font-size: 10px; cursor: pointer; }
.actions button:disabled { opacity: .6; cursor: wait; }
.approve-button { color: #fff; background: #30313a; }
.approve-button:hover { background: #484951; }
.reject-button { border-color: #e8e2e3 !important; color: #a64b55; background: #fff; }
.reject-button:hover { background: #fff5f5; }
.message { margin: 0 0 14px; font-size: 11px; }
.message.error { color: #b94a57; }
.message.success { color: #328165; }
.empty-state { display: flex; min-height: 250px; flex-direction: column; align-items: center; justify-content: center; color: #8c909d; text-align: center; font-size: 11px; }
.empty-icon { display: grid; width: 44px; height: 44px; place-items: center; margin-bottom: 12px; border-radius: 13px; color: #45876d; background: #e9f7f0; font-size: 19px; }
.empty-state strong { color: #5c606d; font-size: 12px; }
.empty-state p { margin: 7px 0 0; }
.evidence-modal { position: fixed; z-index: 20; inset: 0; display: grid; padding: 20px; place-items: center; background: rgba(25, 27, 38, .62); }
.evidence-dialog { position: relative; width: min(100%, 720px); max-height: 90vh; overflow: auto; padding: 22px; border-radius: 12px; background: #fff; }
.evidence-dialog h2 { margin: 0 0 15px; font-size: 15px; }
.evidence-dialog img { display: block; max-width: 100%; max-height: 75vh; margin: 0 auto; object-fit: contain; }
.close-modal { position: absolute; top: 10px; right: 12px; width: 30px; height: 30px; border: 0; border-radius: 50%; color: #646875; background: #f0f1f5; font-size: 20px; cursor: pointer; }
@media (max-width: 650px) {
  .admin-page { padding: 26px 14px 40px; }
  .admin-heading { align-items: flex-start; }
  .admin-heading p:not(.eyebrow) { max-width: 310px; line-height: 1.6; }
  .stats-row { gap: 8px; }
  .stat-card { min-height: 72px; padding: 12px; align-items: flex-start; flex-direction: column-reverse; gap: 3px; }
  .stat-card strong { font-size: 20px; }
  .stat-card span { font-size: 9px; }
  .requests-panel { padding: 15px 12px; }
  .request-card { align-items: stretch; flex-direction: column; gap: 14px; padding: 14px; }
  .actions { justify-content: flex-end; }
  .employee span { display: block; margin: 4px 0 0; }
}
</style>
