<template>
  <main class="login-page">
    <section class="login-card" aria-label="เข้าสู่ระบบ">
      <aside class="brand-panel">
        <div class="brand-lockup">
          <span class="brand-mark" aria-hidden="true">N</span>
          <span class="brand-name">NON<span>.</span></span>
        </div>

        <div class="brand-copy">
          <span class="eyebrow">ยินดีต้อนรับ</span>
          <h1>พื้นที่ของคุณ<br />เริ่มต้นที่นี่</h1>
          <p>จัดการทุกสิ่งได้ง่ายขึ้น ในพื้นที่ทำงานที่ออกแบบมาเพื่อคุณ</p>
        </div>

        <div class="brand-footer">
          <span class="status-dot"></span>
          <span>พื้นที่ทำงานของคุณ ปลอดภัยเสมอ</span>
        </div>
        <div class="decor decor-one" aria-hidden="true"></div>
        <div class="decor decor-two" aria-hidden="true"></div>
      </aside>

      <div class="form-panel">
        <router-link class="back-link" to="/">
          <span aria-hidden="true">←</span>
          กลับหน้าต้อนรับ
        </router-link>

        <div class="mobile-brand brand-lockup">
          <span class="brand-mark" aria-hidden="true">N</span>
          <span class="brand-name">NON<span>.</span></span>
        </div>

        <div class="form-heading">
          <span class="eyebrow">เข้าสู่ระบบบัญชีของคุณ</span>
          <h2>ล็อกอินเข้าสู่ระบบที่นี่</h2>
          <p>กรอกข้อมูลเพื่อเข้าสู่พื้นที่ทำงานของคุณ</p>
        </div>

        <form class="login-form" @submit.prevent="handleSubmit">
          <label for="email">อีเมล</label>
          <div class="input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <rect x="3.5" y="5" width="17" height="14" rx="3"></rect>
              <path d="m4.5 7 7.5 6 7.5-6"></path>
            </svg>
            <input
              id="email"
              v-model="email"
              type="email"
              placeholder="you@example.com"
              autocomplete="email"
              required
            />
          </div>

          <div class="password-label-row">
            <label for="password">รหัสผ่าน</label>
            <a href="#forgot-password" @click.prevent="showNotice('กรุณาติดต่อผู้ดูแลระบบเพื่อรีเซ็ตรหัสผ่าน')">ลืมรหัสผ่าน?</a>
          </div>
          <div class="input-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <rect x="4.5" y="10" width="15" height="11" rx="3"></rect>
              <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"></path>
            </svg>
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="กรอกรหัสผ่านของคุณ"
              autocomplete="current-password"
              required
              minlength="6"
            />
            <button
              class="visibility-button"
              type="button"
              :aria-label="showPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'"
              @click="showPassword = !showPassword"
            >
              <svg v-if="!showPassword" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"></path>
                <circle cx="12" cy="12" r="2.5"></circle>
              </svg>
              <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                <path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6.1 0 9.5 6 9.5 6a16 16 0 0 1-3.1 3.6M6.2 6.5C3.8 8.1 2.5 12 2.5 12s3.4 6 9.5 6c1 0 1.9-.2 2.7-.5"></path>
              </svg>
            </button>
          </div>

          <label class="remember-option">
            <input v-model="rememberMe" type="checkbox" />
            <span class="custom-checkbox" aria-hidden="true"></span>
            <span>จดจำฉันไว้ในระบบ</span>
          </label>

          <button class="submit-button" type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'กำลังเข้าสู่ระบบ...' : 'เข้าสู่ระบบ' }}
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"></path></svg>
          </button>
          <p v-if="notice" class="form-notice" :class="noticeType" role="status">{{ notice }}</p>
        </form>

        <p class="signup-prompt">ยังไม่มีบัญชี? <a href="#signup" @click.prevent="showNotice('การสมัครสมาชิกยังไม่พร้อมใช้งาน')">สมัครสมาชิก</a></p>
        <p class="legal-note">การเข้าสู่ระบบแสดงว่าคุณยอมรับ <a href="#terms">เงื่อนไขการใช้งาน</a><br />และ <a href="#privacy">นโยบายความเป็นส่วนตัว</a></p>
      </div>
    </section>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const LOGIN_API_URL = 'http://localhost/new_project_68701996/php/login.php'
const router = useRouter()
const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const showPassword = ref(false)
const notice = ref('')
const noticeType = ref('')
const isSubmitting = ref(false)

function showNotice(message) {
  notice.value = message
  noticeType.value = ''
}

async function handleSubmit() {
  isSubmitting.value = true
  notice.value = ''
  noticeType.value = ''

  try {
    const response = await fetch(LOGIN_API_URL, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: email.value,
        password: password.value,
        rememberMe: rememberMe.value,
      }),
    })
    const result = await response.json()

    if (!response.ok || !result.success) {
      throw new Error(result.message || 'เข้าสู่ระบบไม่สำเร็จ')
    }

    localStorage.setItem('non-user', JSON.stringify(result.user || { email: email.value }))
    await router.push(result.user?.isManager ? '/admin' : '/dayoff')
  } catch (error) {
    notice.value = error instanceof TypeError
      ? 'เชื่อมต่อระบบไม่ได้ กรุณาตรวจสอบว่า Apache และ MySQL ทำงานอยู่'
      : error.message
    noticeType.value = 'is-error'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap');

:root {
  font-family: 'DM Sans', 'Noto Sans Thai', sans-serif;
  color: #20232f;
  font-synthesis: none;
  text-rendering: optimizeLegibility;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

* { box-sizing: border-box; }

body {
  min-width: 320px;
  min-height: 100vh;
  margin: 0;
  background: #f3f5f9;
}

button, input { font: inherit; }

.login-page {
  display: grid;
  min-height: 100vh;
  padding: 48px 24px;
  place-items: center;
  background:
    radial-gradient(ellipse at 10% 12%, rgba(119, 133, 255, .09), transparent 32%),
    radial-gradient(ellipse at 92% 90%, rgba(78, 201, 177, .08), transparent 30%),
    #f5f6fa;
}

.login-card {
  display: grid;
  grid-template-columns: minmax(330px, .92fr) minmax(440px, 1.08fr);
  width: min(100%, 1040px);
  min-height: 650px;
  overflow: hidden;
  border: 1px solid rgba(31, 41, 74, .06);
  border-radius: 28px;
  background: #fff;
  box-shadow: 0 28px 80px rgba(38, 48, 80, .12);
}

.brand-panel {
  position: relative;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding: 42px 44px 34px;
  color: #fff;
  background: linear-gradient(145deg, #252b52 0%, #353d75 55%, #4b568e 100%);
}

.brand-lockup { position: relative; z-index: 1; display: flex; align-items: center; gap: 11px; }
.brand-mark { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 12px; color: #29305c; background: #c9f4e7; font-size: 21px; font-weight: 700; }
.brand-name { color: inherit; font-size: 19px; font-weight: 700; letter-spacing: 2px; }
.brand-name span { color: #71dfc0; }

.brand-copy { position: relative; z-index: 1; margin: auto 0; padding: 36px 0 58px; }
.eyebrow { color: #7c87a6; font-size: 12px; font-weight: 700; letter-spacing: .08em; }
.brand-panel .eyebrow { color: #aeb8db; }
.brand-copy h1 { margin: 17px 0 16px; font-size: clamp(32px, 4vw, 43px); font-weight: 600; letter-spacing: -.04em; line-height: 1.35; }
.brand-copy p { max-width: 310px; margin: 0; color: #c1c8e0; font-size: 14px; line-height: 1.9; }
.brand-footer { position: relative; z-index: 1; display: flex; align-items: center; gap: 10px; color: #c1c8e0; font-size: 12px; }
.status-dot { width: 8px; height: 8px; border-radius: 50%; background: #71dfc0; box-shadow: 0 0 0 4px rgba(113, 223, 192, .13); }
.decor { position: absolute; border: 1px solid rgba(218, 228, 255, .1); border-radius: 50%; }
.decor-one { width: 380px; height: 380px; right: -210px; top: 100px; box-shadow: 0 0 0 38px rgba(218, 228, 255, .025), 0 0 0 78px rgba(218, 228, 255, .025); }
.decor-two { width: 240px; height: 240px; left: -175px; bottom: -100px; background: rgba(149, 166, 255, .07); }

.form-panel { display: flex; flex-direction: column; justify-content: center; padding: 60px clamp(36px, 6vw, 76px); }
.mobile-brand { display: none; color: #29305c; }
.form-heading { margin-bottom: 32px; }
.form-heading h2 { margin: 9px 0 8px; color: #20232f; font-size: 27px; font-weight: 700; letter-spacing: -.035em; }
.form-heading p { margin: 0; color: #8990a2; font-size: 13px; }
.login-form label { display: block; margin-bottom: 9px; color: #363b4c; font-size: 12px; font-weight: 600; }
.input-wrap { display: flex; align-items: center; height: 50px; margin-bottom: 22px; padding: 0 14px; border: 1px solid #e5e8ef; border-radius: 10px; background: #fff; transition: border-color .2s, box-shadow .2s; }
.input-wrap:focus-within { border-color: #6674c9; box-shadow: 0 0 0 3px rgba(102, 116, 201, .12); }
.input-wrap > svg { flex: 0 0 18px; width: 18px; height: 18px; margin-right: 11px; fill: none; stroke: #9da4b4; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.6; }
.input-wrap input { width: 100%; min-width: 0; border: 0; outline: 0; color: #282d3b; background: transparent; font-size: 13px; }
.input-wrap input::placeholder { color: #b4bac6; }
.password-label-row { display: flex; align-items: center; justify-content: space-between; }
.password-label-row a, .signup-prompt a, .legal-note a { color: #5665bc; font-weight: 600; text-decoration: none; }
.password-label-row a { margin-bottom: 9px; font-size: 11px; }
.password-label-row a:hover, .signup-prompt a:hover, .legal-note a:hover { text-decoration: underline; }
.visibility-button { display: grid; flex: 0 0 28px; width: 28px; height: 32px; padding: 4px; place-items: center; border: 0; color: #9da4b4; background: transparent; cursor: pointer; }
.visibility-button svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.6; }
.remember-option { display: inline-flex !important; align-items: center; gap: 9px; margin: -1px 0 25px !important; color: #737b8c !important; font-size: 12px !important; font-weight: 400 !important; cursor: pointer; }
.remember-option input { position: absolute; width: 1px; height: 1px; opacity: 0; }
.custom-checkbox { display: grid; width: 16px; height: 16px; place-items: center; border: 1px solid #d6dae4; border-radius: 4px; background: #fff; }
.remember-option input:checked + .custom-checkbox { border-color: #5868bf; background: #5868bf; }
.remember-option input:checked + .custom-checkbox::after { width: 7px; height: 4px; border-bottom: 1.5px solid white; border-left: 1.5px solid white; content: ''; transform: rotate(-45deg) translateY(-1px); }
.remember-option input:focus-visible + .custom-checkbox { outline: 3px solid rgba(88, 104, 191, .2); }
.submit-button { display: flex; width: 100%; height: 50px; align-items: center; justify-content: center; gap: 10px; border: 0; border-radius: 10px; color: #fff; background: #5868bf; box-shadow: 0 8px 18px rgba(88, 104, 191, .2); font-size: 13px; font-weight: 600; cursor: pointer; transition: background .2s, transform .2s, box-shadow .2s; }
.submit-button:hover { transform: translateY(-1px); background: #4b5aaf; box-shadow: 0 11px 22px rgba(88, 104, 191, .25); }
.submit-button:disabled { transform: none; opacity: .7; cursor: wait; }
.submit-button svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
.form-notice { margin: 12px 0 0; color: #5362b4; font-size: 12px; line-height: 1.6; }
.form-notice.is-success { color: #218364; }
.form-notice.is-error { color: #c34755; }
.signup-prompt { margin: 24px 0 29px; color: #858b9a; text-align: center; font-size: 12px; }
.signup-prompt a { margin-left: 3px; }
.legal-note { margin: auto 0 0; color: #a1a6b2; text-align: center; font-size: 10px; line-height: 1.9; }
.legal-note a { color: #767e98; font-weight: 500; }

@media (max-width: 760px) {
  .login-page { padding: 24px 16px; }
  .login-card { grid-template-columns: 1fr; width: min(100%, 490px); min-height: 0; border-radius: 22px; }
  .brand-panel { display: none; }
  .form-panel { padding: 32px clamp(24px, 8vw, 42px) 28px; }
  .mobile-brand { display: flex; margin-bottom: 34px; }
  .mobile-brand .brand-mark { background: #e2e7ff; }
  .form-heading { margin-bottom: 27px; }
  .form-heading h2 { font-size: 25px; }
  .signup-prompt { margin: 22px 0 26px; }
}

@media (max-width: 380px) {
  .login-page { padding: 14px 10px; }
  .form-panel { padding-right: 21px; padding-left: 21px; }
}

/* Minimal login layout */
.login-page {
  padding: 32px 20px;
  min-height: calc(100vh - 66px);
  background: #f7f7f8;
}

.login-card {
  display: block;
  width: min(100%, 440px);
  min-height: 0;
  border: 1px solid #ececf0;
  border-radius: 16px;
  box-shadow: 0 12px 40px rgba(20, 20, 30, .06);
}

.brand-panel { display: none; }

.form-panel {
  min-height: 0;
  padding: 40px;
}

.back-link {
  display: inline-flex;
  align-self: flex-start;
  align-items: center;
  gap: 7px;
  margin-bottom: 26px;
  color: #777985;
  font-size: 12px;
  text-decoration: none;
}

.back-link:hover { color: #292a31; }
.back-link span { font-size: 17px; line-height: 1; }

.mobile-brand {
  display: flex;
  margin-bottom: 36px;
}

.mobile-brand .brand-mark {
  color: #fff;
  background: #292a31;
}

.brand-name { color: #292a31; }
.brand-name span { color: #777985; }
.eyebrow { color: #777985; letter-spacing: 0; }
.form-heading { margin-bottom: 28px; }
.form-heading h2 { font-size: 25px; letter-spacing: -.02em; }
.form-heading p { color: #777985; line-height: 1.7; }
.input-wrap { border-color: #e3e3e8; border-radius: 8px; }
.input-wrap:focus-within { border-color: #777985; box-shadow: 0 0 0 3px rgba(60, 60, 70, .08); }
.password-label-row a, .signup-prompt a { color: #34353d; }
.submit-button {
  border-radius: 8px;
  background: #292a31;
  box-shadow: none;
}
.submit-button:hover { background: #41424a; box-shadow: none; }
.signup-prompt { color: #777985; }
.legal-note { color: #9697a0; }
.legal-note a { color: #696a73; }

@media (max-width: 760px) {
  .login-page { padding: 20px 14px; }
  .form-panel { padding: 32px 26px; }
  .mobile-brand { margin-bottom: 30px; }
}
</style>
