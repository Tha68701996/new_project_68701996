<template>
	<AppNavbar />
	<main v-if="route.path === '/'" class="welcome-page">
		<section class="welcome-card">
			<div class="welcome-brand" aria-label="NON">
				<span class="welcome-mark">N</span>
				<span>NON<span class="brand-period">.</span></span>
			</div>

			<div class="welcome-content">
				<p class="welcome-eyebrow">ยินดีต้อนรับ</p>
				<h1>ทุกอย่างที่คุณต้องการ<br />เริ่มต้นได้ที่นี่</h1>
				<p class="welcome-description">
					เข้าสู่พื้นที่ทำงานของคุณ เพื่อจัดการงานและสิ่งสำคัญได้อย่างง่ายดาย
				</p>
				<router-link class="welcome-button" to="/login">
					ไปยังหน้าเข้าสู่ระบบ
					<span aria-hidden="true">→</span>
				</router-link>
			</div>

			<p class="welcome-footer">พื้นที่ทำงานของคุณ ในแบบที่คุณต้องการ</p>
		</section>
	</main>
	<router-view v-else />
</template>

<script setup>
import { useRoute } from 'vue-router'
import AppNavbar from './components/AppNavbar.vue'

const route = useRoute()
</script>

<style>
* { box-sizing: border-box; }

.welcome-page {
	display: grid;
	min-height: 100vh;
	min-height: calc(100vh - 66px);
	padding: 24px;
	place-items: center;
	background: #f7f7f8;
	color: #292a31;
	font-family: 'DM Sans', 'Noto Sans Thai', Arial, sans-serif;
}

.welcome-card {
	display: flex;
	width: min(100%, 720px);
	min-height: 460px;
	flex-direction: column;
	justify-content: space-between;
	padding: clamp(28px, 6vw, 56px);
	border: 1px solid #ececf0;
	border-radius: 18px;
	background: #fff;
	box-shadow: 0 12px 40px rgba(20, 20, 30, .05);
}

.welcome-brand {
	display: flex;
	align-items: center;
	gap: 11px;
	font-size: 17px;
	font-weight: 700;
	letter-spacing: 2px;
}

.welcome-mark {
	display: grid;
	width: 36px;
	height: 36px;
	place-items: center;
	border-radius: 10px;
	color: white;
	background: #292a31;
	font-size: 20px;
	letter-spacing: 0;
}

.brand-period { color: #777985; }
.welcome-content { max-width: 500px; padding: 50px 0; }
.welcome-eyebrow { margin: 0 0 14px; color: #777985; font-size: 13px; font-weight: 600; }
.welcome-content h1 { margin: 0; font-size: clamp(30px, 6vw, 44px); font-weight: 700; letter-spacing: -.04em; line-height: 1.4; }
.welcome-description { max-width: 420px; margin: 16px 0 28px; color: #777985; font-size: 14px; line-height: 1.8; }

.welcome-button {
	display: inline-flex;
	min-height: 48px;
	align-items: center;
	gap: 18px;
	padding: 0 20px;
	border-radius: 8px;
	color: #fff;
	background: #292a31;
	font-size: 13px;
	font-weight: 600;
	text-decoration: none;
	transition: background .2s, transform .2s;
}

.welcome-button:hover { transform: translateY(-1px); background: #41424a; }
.welcome-button span { font-size: 18px; }
.welcome-footer { margin: 0; color: #9697a0; font-size: 11px; }

@media (max-width: 480px) {
	.welcome-page { padding: 14px; }
	.welcome-card { min-height: 420px; border-radius: 14px; }
}
</style>
