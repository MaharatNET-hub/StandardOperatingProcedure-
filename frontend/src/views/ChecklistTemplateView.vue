<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../lib/api'

const categories = ref([])
const loading = ref(true)
const openCategory = ref(null)

const groupLabels = {
  quality: 'محاكاة التصميم بكود نظيف',
  seo: 'الوقاية من مشاكل SEO',
  media: 'الصور، الترجمة، والـ Responsive',
  launch: 'قائمة الإطلاق',
}

// Category form
const showCategoryForm = ref(false)
const editingCategory = ref(null)
const categoryForm = ref({ group: 'quality', code: '', name_ar: '', rule_note: '', order: '' })
const categorySaving = ref(false)
const categoryError = ref('')
const deletingCategory = ref(null)
const deleteCategoryError = ref('')

// Item form
const showItemForm = ref(false)
const editingItem = ref(null)
const itemCategory = ref(null)
const itemForm = ref({ text_ar: '', is_critical: false, order: '' })
const itemSaving = ref(false)
const itemError = ref('')
const deletingItem = ref(null)

const grouped = computed(() => {
  const byGroup = {}
  for (const cat of categories.value) {
    byGroup[cat.group] = byGroup[cat.group] || []
    byGroup[cat.group].push(cat)
  }
  return byGroup
})

async function load() {
  loading.value = true
  const { data } = await api.get('/checklist-categories')
  categories.value = data
  loading.value = false
}

function openCreateCategory() {
  editingCategory.value = null
  categoryForm.value = { group: 'quality', code: '', name_ar: '', rule_note: '', order: '' }
  categoryError.value = ''
  showCategoryForm.value = true
}

function openEditCategory(cat) {
  editingCategory.value = cat
  categoryForm.value = { group: cat.group, code: cat.code, name_ar: cat.name_ar, rule_note: cat.rule_note || '', order: cat.order }
  categoryError.value = ''
  showCategoryForm.value = true
}

async function submitCategory() {
  categoryError.value = ''
  categorySaving.value = true
  try {
    if (editingCategory.value) {
      await api.patch(`/checklist-categories/${editingCategory.value.id}`, categoryForm.value)
    } else {
      await api.post('/checklist-categories', categoryForm.value)
    }
    showCategoryForm.value = false
    await load()
  } catch (e) {
    categoryError.value = e.response?.data?.message || 'حدث خطأ.'
  } finally {
    categorySaving.value = false
  }
}

async function deleteCategory() {
  deleteCategoryError.value = ''
  try {
    await api.delete(`/checklist-categories/${deletingCategory.value.id}`)
    deletingCategory.value = null
    await load()
  } catch (e) {
    deleteCategoryError.value = e.response?.data?.message || 'تعذر حذف القسم.'
  }
}

function openCreateItem(cat) {
  editingItem.value = null
  itemCategory.value = cat
  itemForm.value = { text_ar: '', is_critical: false, order: '' }
  itemError.value = ''
  showItemForm.value = true
}

function openEditItem(cat, item) {
  editingItem.value = item
  itemCategory.value = cat
  itemForm.value = { text_ar: item.text_ar, is_critical: item.is_critical, order: item.order }
  itemError.value = ''
  showItemForm.value = true
}

async function submitItem() {
  itemError.value = ''
  itemSaving.value = true
  try {
    if (editingItem.value) {
      await api.patch(`/checklist-items/${editingItem.value.id}`, itemForm.value)
    } else {
      await api.post(`/checklist-categories/${itemCategory.value.id}/items`, itemForm.value)
    }
    showItemForm.value = false
    await load()
  } catch (e) {
    itemError.value = e.response?.data?.message || 'حدث خطأ.'
  } finally {
    itemSaving.value = false
  }
}

async function deleteItem() {
  await api.delete(`/checklist-items/${deletingItem.value.id}`)
  deletingItem.value = null
  await load()
}

onMounted(load)
</script>

<template>
  <div class="p-6 md:p-8 max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-2">
      <h1 class="text-2xl font-bold text-slate-900">إدارة قائمة التحقق</h1>
      <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2" @click="openCreateCategory">
        + قسم جديد
      </button>
    </div>
    <p class="text-sm text-slate-500 mb-6">
      أي بند جديد تضيفه هنا يظهر فوراً في كل المشاريع الحالية (بحالة "لم يُنجز")، بدون التأثير على أي بيانات أو تقدّم موجود.
    </p>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else v-for="(cats, group) in grouped" :key="group" class="mb-6">
      <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-3">{{ groupLabels[group] || group }}</h2>

      <div class="space-y-3">
        <div v-for="cat in cats" :key="cat.id" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
          <div class="flex items-center justify-between p-4">
            <button class="flex-1 text-right flex items-center gap-2" @click="openCategory = openCategory === cat.id ? null : cat.id">
              <span class="text-xs font-mono text-slate-400">{{ cat.code }}</span>
              <span class="font-medium text-slate-900">{{ cat.name_ar }}</span>
              <span class="text-xs text-slate-400">({{ cat.items.length }} بند)</span>
            </button>
            <div class="flex items-center gap-3 text-xs shrink-0">
              <button class="text-indigo-600 hover:underline" @click="openEditCategory(cat)">تعديل القسم</button>
              <button class="text-red-500 hover:underline" @click="deletingCategory = cat; deleteCategoryError = ''">حذف القسم</button>
              <button class="text-slate-400" @click="openCategory = openCategory === cat.id ? null : cat.id">
                {{ openCategory === cat.id ? '−' : '+' }}
              </button>
            </div>
          </div>

          <div v-if="openCategory === cat.id" class="border-t border-slate-100 p-4 space-y-2">
            <p v-if="cat.rule_note" class="text-xs bg-amber-50 text-amber-800 rounded-lg p-2.5 mb-2">{{ cat.rule_note }}</p>

            <div v-for="item in cat.items" :key="item.id" class="flex items-start justify-between gap-3 border border-slate-100 rounded-lg p-3">
              <p class="text-sm text-slate-800 flex-1">
                {{ item.text_ar }}
                <span v-if="item.is_critical" class="text-red-500 text-xs font-bold ms-1">⚠ حرج</span>
              </p>
              <div class="flex gap-3 text-xs shrink-0">
                <button class="text-indigo-600 hover:underline" @click="openEditItem(cat, item)">تعديل</button>
                <button class="text-red-500 hover:underline" @click="deletingItem = item">حذف</button>
              </div>
            </div>

            <button class="text-sm text-indigo-600 hover:underline mt-2" @click="openCreateItem(cat)">+ إضافة بند لهذا القسم</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Category form -->
    <div v-if="showCategoryForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">{{ editingCategory ? 'تعديل القسم' : 'قسم جديد' }}</h2>
        <form class="space-y-4" @submit.prevent="submitCategory">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">المجموعة</label>
            <select v-model="categoryForm.group" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="quality">محاكاة التصميم بكود نظيف</option>
              <option value="seo">الوقاية من مشاكل SEO</option>
              <option value="media">الصور، الترجمة، والـ Responsive</option>
              <option value="launch">قائمة الإطلاق</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">الرمز (مثال: 3.6)</label>
            <input v-model="categoryForm.code" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">اسم القسم</label>
            <input v-model="categoryForm.name_ar" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">ملاحظة القاعدة العامة (اختياري)</label>
            <textarea v-model="categoryForm.rule_note" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">ترتيب العرض</label>
            <input v-model="categoryForm.order" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>

          <p v-if="categoryError" class="text-sm text-red-600">{{ categoryError }}</p>

          <div class="flex gap-3 justify-end pt-2">
            <button type="button" class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="showCategoryForm = false">إلغاء</button>
            <button type="submit" :disabled="categorySaving" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium disabled:opacity-60">
              {{ categorySaving ? '...جاري الحفظ' : 'حفظ' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Item form -->
    <div v-if="showItemForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-1">{{ editingItem ? 'تعديل البند' : 'بند جديد' }}</h2>
        <p class="text-xs text-slate-400 mb-4">القسم: {{ itemCategory?.name_ar }}</p>
        <form class="space-y-4" @submit.prevent="submitItem">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">نص البند</label>
            <textarea v-model="itemForm.text_ar" required rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          </div>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" v-model="itemForm.is_critical" class="rounded border-slate-300" />
            بند حرج (⚠)
          </label>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">ترتيب العرض</label>
            <input v-model="itemForm.order" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>

          <p v-if="itemError" class="text-sm text-red-600">{{ itemError }}</p>

          <div class="flex gap-3 justify-end pt-2">
            <button type="button" class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="showItemForm = false">إلغاء</button>
            <button type="submit" :disabled="itemSaving" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium disabled:opacity-60">
              {{ itemSaving ? '...جاري الحفظ' : 'حفظ' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete category confirm -->
    <div v-if="deletingCategory" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-2">حذف القسم</h2>
        <p class="text-sm text-slate-600 mb-3">
          حذف قسم "<strong>{{ deletingCategory.name_ar }}</strong>". يجب أن يكون القسم فارغاً من البنود أولاً.
        </p>
        <p v-if="deleteCategoryError" class="text-sm text-red-600 mb-3">{{ deleteCategoryError }}</p>
        <div class="flex gap-3 justify-end">
          <button class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="deletingCategory = null">إلغاء</button>
          <button class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium" @click="deleteCategory">حذف نهائياً</button>
        </div>
      </div>
    </div>

    <!-- Delete item confirm -->
    <div v-if="deletingItem" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-2">حذف البند</h2>
        <p class="text-sm text-slate-600 mb-5">
          سيتم حذف البند من كل المشاريع (بما فيها أي ملاحظات أو أدلة أو نتائج مراجعة جودة مرتبطة به). هل أنت متأكد؟
        </p>
        <div class="flex gap-3 justify-end">
          <button class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="deletingItem = null">إلغاء</button>
          <button class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium" @click="deleteItem">حذف نهائياً</button>
        </div>
      </div>
    </div>
  </div>
</template>
