<template>
	<main class="leave-page">
		<div class="page-content">
			<section class="page-heading">
				<div>
					<p class="eyebrow">WORKSPACE / LEAVE</p>
					<h1>ระบบจัดการวันลา</h1>
					<p class="subheading">ส่งคำขอลาและติดตามสถานะได้ในที่เดียว</p>
				</div>
				<span class="today-chip">{{ todayLabel }}</span>
			</section>

			<section class="summary-grid" aria-label="สรุปวันลา">
				<article class="summary-card">
					<span class="summary-icon purple">▦</span>
					<div><p>วันลาคงเหลือ</p><strong>12 <small>วัน</small></strong></div>
					<span class="summary-note">สิทธิ์ประจำปี</span>
				</article>
				<article class="summary-card">
					<span class="summary-icon amber">◷</span>
					<div><p>รออนุมัติ</p><strong>{{ pendingCount }} <small>รายการ</small></strong></div>
					<span class="summary-note">กำลังดำเนินการ</span>
				</article>
				<article class="summary-card">
					<span class="summary-icon green">✓</span>
					<div><p>อนุมัติแล้ว</p><strong>{{ approvedCount }} <small>รายการ</small></strong></div>
					<span class="summary-note">คำขอทั้งหมด</span>
				</article>
			</section>

			<div class="content-grid">
				<section class="panel request-panel">
					<div class="panel-heading">
						<div><span class="heading-icon">＋</span><h2>ยื่นคำขอลา</h2></div>
						<span class="required-note">* จำเป็นต้องกรอก</span>
					</div>

					<form class="leave-form" @submit.prevent="submitRequest">
						<label for="leave-type">ประเภทการลา <span>*</span></label>
						<select id="leave-type" v-model="form.type" required>
							<option disabled value="">เลือกประเภทการลา</option>
							<option>ลาป่วย</option>
							<option>ลากิจ</option>
							<option>ลาพักร้อน</option>
							<option>ลาอื่น ๆ</option>
						</select>

						<div class="date-fields">
							<div>
								<label for="start-date">วันเริ่มต้น <span>*</span></label>
								<input id="start-date" v-model="form.startDate" type="date" :min="today" required />
							</div>
							<div>
								<label for="end-date">วันสิ้นสุด <span>*</span></label>
								<input id="end-date" v-model="form.endDate" type="date" :min="form.startDate || today" required />
							</div>
						</div>

						<div v-if="dayCount > 0" class="duration-note">รวมวันลาทั้งหมด <strong>{{ dayCount }} วัน</strong> (นับรวมวันเริ่มต้นและวันสิ้นสุด)</div>

						<label for="reason">เหตุผลการลา <span>*</span></label>
						<textarea id="reason" v-model.trim="form.reason" rows="4" maxlength="500" placeholder="ระบุเหตุผลหรือรายละเอียดเพิ่มเติม" required></textarea>
						<div class="character-count">{{ form.reason.length }}/500 ตัวอักษร</div>

						<p v-if="formError" class="form-message error" role="alert">{{ formError }}</p>
						<p v-if="formSuccess" class="form-message success" role="status">{{ formSuccess }}</p>
						<button class="submit-button" type="submit">ส่งคำขอลา <span aria-hidden="true">→</span></button>
						<p class="privacy-note">คำขอของคุณจะถูกส่งให้ผู้ดูแลตรวจสอบ</p>
					</form>
				</section>

				<section class="panel history-panel">
					<div class="panel-heading history-heading">
						<div><span class="heading-icon history-icon">☷</span><h2>ประวัติการลา</h2></div>
						<span class="request-count">{{ requests.length }} รายการ</span>
					</div>

					<div v-if="requests.length" class="request-list">
						<article v-for="request in requests" :key="request.id" class="request-item">
							<div class="request-topline">
								<span class="leave-type-badge">{{ request.type }}</span>
								<span class="status-badge" :class="statusClass(request.status)">{{ statusLabel(request.status) }}</span>
							</div>
							<p class="request-dates">{{ formatDate(request.startDate) }} — {{ formatDate(request.endDate) }}</p>
							<p class="request-reason">{{ request.reason }}</p>
							<div class="request-footer">
								<span>{{ request.days }} วัน</span>
								<button v-if="request.status === 'pending'" class="cancel-button" type="button" @click="cancelRequest(request.id)">ยกเลิกคำขอ</button>
							</div>
						</article>
					</div>
					<div v-else class="empty-state">
						<span class="empty-icon" aria-hidden="true">▤</span>
						<strong>ยังไม่มีประวัติการลา</strong>
						<p>เมื่อคุณส่งคำขอลา รายการจะแสดงที่นี่</p>
					</div>
					<p v-if="historyError" class="form-message error" role="alert">{{ historyError }}</p>
					<p class="demo-note">ข้อมูลคำขอถูกบันทึกในฐานข้อมูล</p>
				</section>
			</div>
		</div>
	</main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const API_URL = 'http://localhost/new_project_68701996/php/dayoff.php'
const router = useRouter()
const today = new Date().toISOString().slice(0, 10)
const form = reactive({ type: '', startDate: '', endDate: '', reason: '' })
const formError = ref('')
const formSuccess = ref('')
const historyError = ref('')
const isSubmitting = ref(false)
const requests = ref([])

const todayLabel = new Intl.DateTimeFormat('th-TH', {
	day: 'numeric', month: 'long', year: 'numeric'
}).format(new Date())
const dayCount = computed(() => {
	if (!form.startDate || !form.endDate || form.endDate < form.startDate) return 0
	return Math.floor((new Date(`${form.endDate}T00:00:00`) - new Date(`${form.startDate}T00:00:00`)) / 86400000) + 1
})
const pendingCount = computed(() => requests.value.filter(item => item.status === 'pending').length)
const approvedCount = computed(() => requests.value.filter(item => item.status === 'approved').length)

onMounted(loadRequests)

async function apiRequest(method = 'GET', payload) {
	const options = { method, credentials: 'include' }
	if (payload) {
		options.headers = { 'Content-Type': 'application/json' }
		options.body = JSON.stringify(payload)
	}
	const response = await fetch(API_URL, options)
	const result = await response.json()
	if (!response.ok || !result.success) {
		throw new Error(result.message || 'ทำรายการไม่สำเร็จ')
	}
	return result
}

async function loadRequests() {
	historyError.value = ''
	try {
		const result = await apiRequest()
		requests.value = result.requests
	} catch (error) {
		historyError.value = error instanceof TypeError
			? 'เชื่อมต่อระบบไม่ได้ ตรวจสอบ Apache และ MySQL'
			: error.message
		if (historyError.value.includes('เข้าสู่ระบบ')) {
			localStorage.removeItem('non-user')
			router.push('/login')
		}
	}
}

async function submitRequest() {
	formError.value = ''
	formSuccess.value = ''
	if (form.endDate < form.startDate) {
		formError.value = 'วันสิ้นสุดต้องไม่อยู่ก่อนวันเริ่มต้น'
		return
	}

	isSubmitting.value = true
	try {
		await apiRequest('POST', form)
		Object.assign(form, { type: '', startDate: '', endDate: '', reason: '' })
		formSuccess.value = 'ส่งคำขอลาเรียบร้อยแล้ว'
		await loadRequests()
	} catch (error) {
		formError.value = error instanceof TypeError
			? 'เชื่อมต่อระบบไม่ได้ ตรวจสอบ Apache และ MySQL'
			: error.message
	} finally {
		isSubmitting.value = false
	}
}

async function cancelRequest(id) {
	try {
		await apiRequest('DELETE', { id })
		await loadRequests()
	} catch (error) {
		historyError.value = error.message
	}
}

function statusClass(status) {
	return status === 'approved' ? 'approved' : status === 'rejected' ? 'rejected' : 'pending'
}

function statusLabel(status) {
	return { pending: 'รออนุมัติ', approved: 'อนุมัติแล้ว', rejected: 'ไม่อนุมัติ' }[status] || status
}

function formatDate(value) {
	return new Intl.DateTimeFormat('th-TH', { day: 'numeric', month: 'short', year: 'numeric' })
		.format(new Date(`${value}T00:00:00`))
}

</script>

<style scoped>
* { box-sizing: border-box; }
.leave-page { min-height: calc(100vh - 66px); color: #242632; background: #f7f8fb; font-family: 'DM Sans', 'Noto Sans Thai', sans-serif; }
.topbar { display: flex; height: 74px; align-items: center; justify-content: space-between; padding: 0 max(28px, calc((100% - 1180px) / 2)); border-bottom: 1px solid #eceef3; background: #fff; }
.brand { display: inline-flex; align-items: center; gap: 10px; color: #292a31; font-size: 17px; font-weight: 700; letter-spacing: 1.6px; text-decoration: none; }
.brand-mark { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 10px; color: white; background: #292a31; font-size: 18px; letter-spacing: 0; }
.brand-period { color: #777985; }
.account-area { display: flex; align-items: center; gap: 10px; }
.avatar { display: grid; width: 36px; height: 36px; place-items: center; border-radius: 50%; color: #474c69; background: #eceeff; font-size: 14px; font-weight: 700; }
.account-copy { display: grid; gap: 2px; min-width: 100px; }
.account-copy strong { font-size: 12px; font-weight: 600; }
.account-copy span { color: #9094a1; font-size: 10px; }
.logout-button { margin-left: 12px; padding: 8px 12px; border: 1px solid #e6e7ec; border-radius: 7px; color: #666b79; background: #fff; font: inherit; font-size: 11px; cursor: pointer; }
.logout-button:hover { border-color: #c8cad3; color: #292a31; }
.page-content { width: min(100% - 48px, 1180px); margin: 0 auto; padding: 40px 0 56px; }
.page-heading { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 25px; }
.eyebrow { margin: 0 0 8px; color: #9398a7; font-size: 10px; font-weight: 700; letter-spacing: .12em; }
.page-heading h1 { margin: 0; font-size: 27px; letter-spacing: -.03em; }
.subheading { margin: 8px 0 0; color: #858a98; font-size: 12px; }
.today-chip { padding: 9px 13px; border: 1px solid #e9eaf0; border-radius: 8px; color: #707584; background: #fff; font-size: 11px; }
.summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 22px; }
.summary-card { position: relative; display: flex; min-height: 112px; align-items: center; gap: 14px; padding: 20px; border: 1px solid #eceef3; border-radius: 12px; background: #fff; }
.summary-icon { display: grid; width: 42px; height: 42px; flex: 0 0 42px; place-items: center; border-radius: 12px; font-size: 20px; font-weight: 600; }
.summary-icon.purple { color: #6665ba; background: #f0efff; }
.summary-icon.amber { color: #bd8a34; background: #fff5e3; }
.summary-icon.green { color: #3a9975; background: #e8f7f0; }
.summary-card p { margin: 0 0 7px; color: #818695; font-size: 11px; }
.summary-card strong { font-size: 23px; line-height: 1; }
.summary-card strong small { color: #8d91a0; font-size: 10px; font-weight: 500; }
.summary-note { position: absolute; right: 16px; bottom: 14px; color: #a0a4af; font-size: 9px; }
.content-grid { display: grid; grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr); align-items: start; gap: 20px; }
.panel { min-height: 490px; padding: 24px; border: 1px solid #eceef3; border-radius: 12px; background: #fff; }
.panel-heading { display: flex; align-items: center; justify-content: space-between; margin-bottom: 23px; }
.panel-heading > div { display: flex; align-items: center; gap: 10px; }
.panel-heading h2 { margin: 0; font-size: 15px; font-weight: 700; }
.heading-icon { display: grid; width: 28px; height: 28px; place-items: center; border-radius: 8px; color: #fff; background: #30313a; font-size: 17px; }
.history-icon { color: #6269a7; background: #f0f1ff; font-size: 15px; }
.required-note { color: #a0a3ae; font-size: 10px; }
.leave-form label { display: block; margin: 0 0 8px; color: #4a4e5b; font-size: 11px; font-weight: 600; }
.leave-form label span { color: #cf5965; }
.leave-form select, .leave-form input, .leave-form textarea { width: 100%; border: 1px solid #e5e7ed; border-radius: 7px; outline: 0; color: #3d414d; background: #fff; font: inherit; font-size: 11px; transition: border-color .2s, box-shadow .2s; }
.leave-form select, .leave-form input { height: 42px; padding: 0 11px; }
.leave-form textarea { min-height: 96px; padding: 11px; resize: vertical; line-height: 1.6; }
.leave-form select:focus, .leave-form input:focus, .leave-form textarea:focus { border-color: #777da9; box-shadow: 0 0 0 3px rgba(100, 105, 160, .09); }
.leave-form select { margin-bottom: 18px; }
.date-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 17px; }
.duration-note { margin: -7px 0 17px; color: #8c909b; font-size: 10px; }
.duration-note strong { color: #555b87; }
.character-count { margin-top: 5px; color: #a0a4af; text-align: right; font-size: 9px; }
.form-message { margin: 12px 0 0; font-size: 11px; line-height: 1.5; }
.form-message.error { color: #bd4d59; }
.form-message.success { color: #328165; }
.submit-button { display: flex; width: 100%; height: 44px; align-items: center; justify-content: center; gap: 10px; margin-top: 16px; border: 0; border-radius: 7px; color: #fff; background: #292a31; font: inherit; font-size: 11px; font-weight: 600; cursor: pointer; transition: background .2s, transform .2s; }
.submit-button:hover { transform: translateY(-1px); background: #44454e; }
.submit-button span { font-size: 15px; }
.privacy-note { margin: 12px 0 0; color: #a1a4ae; text-align: center; font-size: 9px; }
.request-count { color: #8c909e; font-size: 10px; }
.request-list { display: grid; gap: 12px; max-height: 435px; overflow-y: auto; }
.request-item { padding: 15px; border: 1px solid #eceef3; border-radius: 9px; background: #fff; }
.request-topline, .request-footer { display: flex; align-items: center; justify-content: space-between; }
.leave-type-badge { color: #52577d; font-size: 11px; font-weight: 700; }
.status-badge { padding: 5px 8px; border-radius: 20px; font-size: 9px; }
.status-badge.pending { color: #9c742c; background: #fff5df; }
.status-badge.approved { color: #318263; background: #e8f7f0; }
.status-badge.rejected { color: #b94a57; background: #fff0f1; }
.request-dates { margin: 10px 0 5px; color: #5e6370; font-size: 10px; }
.request-reason { margin: 0; overflow-wrap: anywhere; color: #858a98; font-size: 10px; line-height: 1.6; }
.request-footer { margin-top: 12px; padding-top: 10px; border-top: 1px solid #f0f1f4; color: #8c909b; font-size: 9px; }
.cancel-button { padding: 0; border: 0; color: #ba5961; background: transparent; font: inherit; font-size: 9px; cursor: pointer; }
.cancel-button:hover { text-decoration: underline; }
.empty-state { display: flex; min-height: 340px; flex-direction: column; align-items: center; justify-content: center; color: #8d91a0; text-align: center; }
.empty-icon { display: grid; width: 52px; height: 52px; place-items: center; margin-bottom: 13px; border-radius: 15px; color: #8589aa; background: #f3f3fb; font-size: 23px; }
.empty-state strong { color: #5c606d; font-size: 12px; }
.empty-state p { margin: 7px 0 0; font-size: 10px; }
.demo-note { margin: 16px 0 0; color: #a1a4ae; text-align: center; font-size: 9px; }

@media (max-width: 820px) {
	.content-grid { grid-template-columns: 1fr; }
	.panel { min-height: 0; }
	.empty-state { min-height: 230px; }
}
@media (max-width: 600px) {
	.topbar { height: 64px; padding: 0 16px; }
	.account-copy { display: none; }
	.logout-button { margin-left: 2px; }
	.page-content { width: calc(100% - 28px); padding: 28px 0 40px; }
	.page-heading { align-items: flex-start; gap: 12px; }
	.page-heading h1 { font-size: 22px; }
	.today-chip { max-width: 120px; padding: 8px; text-align: center; font-size: 9px; }
	.summary-grid { grid-template-columns: 1fr; gap: 9px; }
	.summary-card { min-height: 86px; padding: 14px; }
	.summary-note { right: 14px; bottom: 12px; }
	.panel { padding: 18px 15px; }
	.date-fields { gap: 8px; }
}
</style>
