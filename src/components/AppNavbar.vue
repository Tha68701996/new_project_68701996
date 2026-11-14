<template>
  <header class="app-navbar">
    <router-link class="nav-brand" to="/" aria-label="NON ไปหน้าต้อนรับ">
      <span class="nav-mark">N</span>
      <span>NON<span class="brand-period">.</span></span>
    </router-link>

    <nav class="nav-links" aria-label="เมนูหลัก">
      <router-link v-if="user" to="/dayoff" class="nav-link">ระบบลางาน</router-link>
      <router-link v-if="isManager" to="/admin" class="nav-link">พิจารณาคำขอ</router-link>
      <router-link v-if="!user" to="/login" class="nav-login">เข้าสู่ระบบ</router-link>
      <div v-else class="nav-user">
        <span class="nav-user-name">{{ user.name || user.email }}</span>
        <button type="button" class="nav-logout" @click="logout">ออกจากระบบ</button>
      </div>
    </nav>
  </header>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const MANAGER_EMAIL = 'suratan999@gmail.com'
const API_URL = 'http://localhost/new_project_68701996/php/dayoff.php'
const route = useRoute()
const router = useRouter()
const user = ref(readUser())
const isManager = computed(() => user.value?.email?.toLowerCase() === MANAGER_EMAIL)

function readUser() {
  try {
    return JSON.parse(localStorage.getItem('non-user') || 'null')
  } catch {
    return null
  }
}

function refreshUser() {
  user.value = readUser()
}

watch(() => route.fullPath, refreshUser)
onMounted(() => window.addEventListener('storage', refreshUser))
onUnmounted(() => window.removeEventListener('storage', refreshUser))

async function logout() {
  try {
    await fetch(`${API_URL}?action=logout`, { method: 'POST', credentials: 'include' })
  } catch {
    // ออกจากระบบฝั่งหน้าเว็บต่อได้ แม้ API จะไม่พร้อมใช้งาน
  }
  localStorage.removeItem('non-user')
  refreshUser()
  await router.push('/login')
}
</script>

<style scoped>
.app-navbar { position: relative; z-index: 5; display: flex; min-height: 66px; align-items: center; justify-content: space-between; padding: 0 max(22px, calc((100% - 1180px) / 2)); border-bottom: 1px solid #eceef3; background: #fff; color: #292a31; font-family: 'DM Sans', 'Noto Sans Thai', Arial, sans-serif; }
.nav-brand { display: inline-flex; align-items: center; gap: 10px; color: inherit; font-size: 16px; font-weight: 700; letter-spacing: 1.6px; text-decoration: none; }
.nav-mark { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 10px; color: #fff; background: #292a31; font-size: 18px; letter-spacing: 0; }
.brand-period { color: #777985; }
.nav-links { display: flex; align-items: center; gap: 22px; }
.nav-link { padding: 8px 0; color: #727684; font-size: 12px; text-decoration: none; }
.nav-link:hover, .nav-link.router-link-active { color: #292a31; }
.nav-login, .nav-logout { border: 1px solid #e5e6eb; border-radius: 7px; color: #4c505c; background: #fff; font: inherit; font-size: 11px; text-decoration: none; }
.nav-login { padding: 9px 14px; }
.nav-logout { padding: 8px 11px; cursor: pointer; }
.nav-login:hover, .nav-logout:hover { border-color: #bfc1ca; }
.nav-user { display: flex; align-items: center; gap: 12px; }
.nav-user-name { max-width: 180px; overflow: hidden; color: #777b88; font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }
@media (max-width: 560px) {
  .app-navbar { min-height: 60px; padding: 0 14px; }
  .nav-links { gap: 12px; }
  .nav-user { gap: 8px; }
  .nav-user-name { display: none; }
  .nav-link { font-size: 10px; }
}
</style>
