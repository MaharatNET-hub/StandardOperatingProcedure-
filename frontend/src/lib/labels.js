// التسميات العربية الموحّدة لحقول المشروع — مصدر واحد تستخدمه كل الصفحات
// واللوحات حتى لا تختلف التسمية بين شاشة وأخرى.

export const pipelineStageLabels = {
  new_project: 'مشروع جديد',
  information_collection: 'جمع المعلومات',
  ready_to_start: 'جاهز للبدء',
  in_progress: 'قيد التنفيذ',
  waiting_client: 'بانتظار العميل',
  waiting_payment: 'بانتظار الدفع',
  waiting_content: 'بانتظار المحتوى',
  internal_review_qa: 'مراجعة داخلية / QA',
  testing: 'اختبار',
  client_review: 'مراجعة العميل',
  changes_requested: 'طلب تعديلات',
  final_review: 'مراجعة نهائية',
  ready_for_launch: 'جاهز للإطلاق',
  live: 'مباشر (Live)',
  completed: 'مكتمل',
  paused: 'متوقف',
  cancelled: 'ملغي',
}

export const projectTypeLabels = {
  ecommerce: 'متجر إلكتروني',
  corporate: 'موقع شركة',
  landing_page: 'صفحة هبوط',
  portfolio: 'معرض أعمال',
  blog: 'مدونة',
  booking: 'موقع حجوزات',
  marketplace: 'سوق إلكتروني',
  custom: 'مخصص',
  mobile_app: 'تطبيق موبايل',
  other: 'أخرى',
}

export const blockerLabels = {
  none: 'لا يوجد',
  waiting_client: 'بانتظار العميل',
  waiting_developer: 'بانتظار المبرمج',
  waiting_payment_gateway: 'بانتظار بوابة الدفع',
  waiting_domain: 'بانتظار الدومين',
  waiting_hosting: 'بانتظار الاستضافة',
  waiting_content: 'بانتظار المحتوى',
  waiting_logo: 'بانتظار الشعار',
  waiting_product_images: 'بانتظار صور المنتجات',
  waiting_shipping: 'بانتظار شركة الشحن',
  other: 'أخرى',
}

export const priorityLabels = { critical: 'حرجة', high: 'عالية', medium: 'متوسطة', low: 'منخفضة' }

export const priorityColors = {
  critical: 'bg-red-100 text-red-700',
  high: 'bg-orange-100 text-orange-700',
  medium: 'bg-amber-100 text-amber-700',
  low: 'bg-slate-100 text-slate-600',
}

// Calc - Update Status: حالة موعد التحديث القادم مع العميل
export const updateStatusLabels = {
  no_schedule: 'لا يوجد جدول',
  ok: 'ضمن الموعد',
  due_tomorrow: 'مستحق غداً',
  due_today: 'مستحق اليوم',
  overdue: 'متأخر',
}

export const updateStatusColors = {
  no_schedule: 'bg-slate-100 text-slate-600',
  ok: 'bg-emerald-100 text-emerald-700',
  due_tomorrow: 'bg-sky-100 text-sky-700',
  due_today: 'bg-amber-100 text-amber-700',
  overdue: 'bg-red-100 text-red-700',
}

export const feedbackLabels = {
  none: 'لا يوجد',
  new: 'جديدة',
  in_progress: 'قيد التنفيذ',
  completed: 'مكتملة',
}

export const noteTypeLabels = {
  client_feedback: 'ملاحظات العميل',
  client_update: 'تحديث للعميل',
  internal_note: 'ملاحظة داخلية',
  decision: 'قرار',
  meeting: 'اجتماع',
}

export const noteTypeColors = {
  client_feedback: 'bg-rose-100 text-rose-700',
  client_update: 'bg-sky-100 text-sky-700',
  internal_note: 'bg-slate-100 text-slate-700',
  decision: 'bg-violet-100 text-violet-700',
  meeting: 'bg-emerald-100 text-emerald-700',
}

export const noteStatusLabels = { new: 'جديدة', in_progress: 'قيد التنفيذ', done: 'منجزة' }

export const noteStatusColors = {
  new: 'bg-amber-100 text-amber-700',
  in_progress: 'bg-indigo-100 text-indigo-700',
  done: 'bg-emerald-100 text-emerald-700',
}

// تخصّص المبرمج
export const specializationLabels = {
  wordpress: 'ووردبريس',
  custom_dev: 'برمجة خاصة',
  flutter: 'فلاتر (تطبيقات موبايل)',
  frontend: 'واجهات أمامية',
  other: 'أخرى',
}

export const specializationColors = {
  wordpress: 'bg-sky-100 text-sky-700',
  custom_dev: 'bg-violet-100 text-violet-700',
  flutter: 'bg-teal-100 text-teal-700',
  frontend: 'bg-amber-100 text-amber-700',
  other: 'bg-slate-100 text-slate-600',
}

export function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleDateString('ar-EG', { year: 'numeric', month: 'short', day: 'numeric' })
}

export function formatDateTime(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('ar-EG', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
