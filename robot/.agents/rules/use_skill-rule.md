---
description: Skills Management System v2.0
---

# Skills Management System v2.0

## 📋 Overview

This rule defines the comprehensive skills management system for XHE automation projects. It implements modern software engineering practices including versioning, lifecycle management, and user-driven updates.

## 🔢 Versioning System

### Skill Version Format
```
MAJOR.MINOR.PATCH-STATUS
```
- **MAJOR**: Breaking changes (incompatible API changes)
- **MINOR**: New functionality (backward compatible)
- **PATCH**: Bug fixes (backward compatible)
- **STATUS**: `dev` | `stable` | `deprecated` | `legacy`

### Version File Structure
Each skill MUST include:
```yaml
---
version: 1.0.0-stable
last_updated: 2024-01-15T14:30:00Z
author: system | user
compatibility: ">=2.0.0"
dependencies: []
changelog:
  - "1.0.0-stable: Initial stable release"
  - "0.9.0-dev: Beta testing"
---
```

## 🔄 Индексация скиллов

- **ВСЕГДА** используй `.agents/skills/INDEX.md` как маршрутизатор
- **НЕ вызывай скиллы напрямую** — только через `.agents/skills/INDEX.md`
- **После использования скилла** → продолжи задачу по INDEX.md: отдельного журнала навыков нет

## 🎯 Жизненный цикл скилла

### 1. Discovery Phase
```
Чтение INDEX.md → Выбор скилла → Проверка версии → Валидация зависимостей
```

### 2. Execution Phase
```
Чтение .agents/skills/[name]/SKILL.md → Выполнение задачи → Валидация результата
```

### 3. Completion Phase
```
Итог скилла — в ответе пользователю → Возврат к INDEX.md
```

## 🛠 Методология обновления скиллов по прямому указанию пользователя

### Update Request Processing

#### Шаг 1: Валидация запроса
- **Формат**: `update skill [skill_name] [instruction]`
- **Проверка прав**: Пользователь должен иметь права на запись
- **Резервное копирование**: Автоматическое создание бэкапа перед обновлением

#### Шаг 2: Анализ инструкции
```python
# Пример анализа инструкции
def analyze_update_instruction(instruction):
    if "добавить" in instruction.lower():
        return "ADD_FEATURE"
    elif "исправить" in instruction.lower():
        return "BUG_FIX"
    elif "оптимизировать" in instruction.lower():
        return "OPTIMIZATION"
    elif "рефакторинг" in instruction.lower():
        return "REFACTOR"
    else:
        return "GENERAL_UPDATE"
```

#### Шаг 3: Версионирование обновления
- **PATCH**: `1.0.0` → `1.0.1` (исправления)
- **MINOR**: `1.0.0` → `1.1.0` (новые функции)
- **MAJOR**: `1.0.0` → `2.0.0` (breaking changes)

#### Шаг 4: Применение обновления
```bash
# Процесс обновления
1. git checkout -b skill-update-[skill_name]-[version]
2. cp .agents/skills/[name]/SKILL.md .agents/skills/[name]/SKILL.md.backup
3. Apply changes based on instruction
4. Validate syntax and dependencies
5. Update version in metadata
6. Test in isolated environment
7. Commit changes with proper message
```

#### Шаг 5: Валидация и тестирование
- **Синтаксическая проверка**: Markdown валидация
- **Функциональное тестирование**: Проверка core functionality
- **Интеграционное тестирование**: Проверка совместимости
- **Производительность**: Бенчмарк до/после

### Update Templates

#### Template 1: Feature Addition
```markdown
---
version: {new_version}
last_updated: {timestamp}
author: user
changelog:
  - "{new_version}: Added {feature_description}"
---

## 🆕 New Feature: {feature_name}

### Description
{feature_description}

### Implementation
{implementation_details}

### Usage Example
```php
// Пример использования
$result = $skill->newFeature($params);
```
```

#### Template 2: Bug Fix
```markdown
---
version: {new_version}
last_updated: {timestamp}
author: user
changelog:
  - "{new_version}: Fixed {bug_description}"
---

## 🐛 Bug Fix: {bug_name}

### Problem
{problem_description}

### Solution
{solution_description}

### Test Cases
- [ ] Test case 1
- [ ] Test case 2
```

## 📊 Современные подходы к управлению скиллами

### 1. Semantic Versioning
- Следуй принципам SemVer
- Автоматическое определение типа обновления
- Обратная совместимость

### 2. Dependency Management
```json
{
  "skill_name": "design-architecture",
  "version": "1.0.0",
  "dependencies": {
    "analyze-tz": ">=1.0.0",
    "core-utils": ">=2.0.0"
  },
  "peerDependencies": {
    "xhe-framework": ">=3.0.0"
  }
}
```

### 3. Continuous Integration
- Автоматическое тестирование при обновлении
- Проверка совместимости
- Деплой в staging среду

### 4. Monitoring & Analytics
```json
{
  "usage_stats": {
    "total_calls": 150,
    "success_rate": 0.98,
    "avg_execution_time": "2.3s",
    "last_used": "2024-01-15T14:30:00Z"
  }
}
```

## 🚀 Best Practices

### 1. Code Quality
- **DRY**: Don't Repeat Yourself
- **KISS**: Keep It Simple, Stupid
- **SOLID**: Принципы объектно-ориентированного дизайна

### 2. Documentation
- **README.md**: Общее описание
- **SKILL.md**: Детальная документация
- **EXAMPLES.md**: Примеры использования
- **CHANGELOG.md**: История изменений

### 3. Testing
- **Unit Tests**: Тестирование отдельных функций
- **Integration Tests**: Тестирование взаимодействия
- **E2E Tests**: Полное тестирование сценария

### 4. Error Handling
```php
try {
    $result = $skill->execute($params);
} catch (SkillException $e) {
    // Логирование ошибки
    Log::error($e->getMessage());
    
    // Возврат к предыдущей версии
    $skill->rollback();
    
    // Уведомление пользователя
    Notification::send($e->getMessage());
}
```

## 🔄 Rollback Procedure

### 1. Automatic Rollback
```bash
# Автоматический откат при ошибке
if [ $exit_code -ne 0 ]; then
    git checkout HEAD~1 .agents/skills/[name]/SKILL.md
    git commit -m "Rollback: Automatic recovery after failed update"
fi
```

### 2. Manual Rollback
```bash
# Ручной откат к конкретной версии
git checkout v1.0.0 .agents/skills/[name]/SKILL.md
git commit -m "Rollback: Manual rollback to v1.0.0"
```

## 📈 Performance Optimization

### 1. Caching Strategy
```php
// Кэширование результатов скиллов
class SkillCache {
    private $cache = [];
    private $ttl = 3600; // 1 hour
    
    public function get($skill, $params) {
        $key = md5($skill . serialize($params));
        
        if (isset($this->cache[$key]) && 
            (time() - $this->cache[$key]['time']) < $this->ttl) {
            return $this->cache[$key]['data'];
        }
        
        $result = $this->executeSkill($skill, $params);
        $this->cache[$key] = [
            'data' => $result,
            'time' => time()
        ];
        
        return $result;
    }
}
```

### 2. Lazy Loading
```php
// Ленивая загрузка скиллов
class SkillLoader {
    private $loadedSkills = [];
    
    public function loadSkill($skillName) {
        if (!isset($this->loadedSkills[$skillName])) {
            $this->loadedSkills[$skillName] = new Skill($skillName);
        }
        
        return $this->loadedSkills[$skillName];
    }
}
```

## 🔒 Security Considerations

### 1. Input Validation
```php
// Валидация входных данных
public function validateInput($data) {
    if (!is_array($data)) {
        throw new InvalidArgumentException("Input must be an array");
    }
    
    // Валидация обязательных полей
    $required = ['action', 'params'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new InvalidArgumentException("Missing required field: $field");
        }
    }
    
    return true;
}
```

### 2. Permission System
```php
// Система прав доступа
class SkillPermissions {
    private $permissions = [
        'admin' => ['create', 'read', 'update', 'delete'],
        'user' => ['read', 'execute'],
        'guest' => ['read']
    ];
    
    public function can($user, $action, $skill) {
        $userRole = $this->getUserRole($user);
        return in_array($action, $this->permissions[$userRole]);
    }
}
```

## 📋 Checklist for Skill Updates

### Pre-Update Checklist
- [ ] Создан бэкап текущей версии
- [ ] Проверены зависимости
- [ ] Составлен план тестирования
- [ ] Получено подтверждение от пользователя

### Post-Update Checklist
- [ ] Все тесты пройдены
- [ ] Документация обновлена
- [ ] Версия в метаданных обновлена
- [ ] Changelog заполнен
- [ ] Пользователь уведомлен об успешном обновлении

## 🎯 Conclusion

This enhanced skills management system provides:
- **Versioning**: Полная система версионирования
- **Updates**: Структурированный процесс обновлений
- **Quality**: Современные подходы к качеству кода
- **Security**: Безопасность и права доступа
- **Performance**: Оптимизация производительности
- **Maintainability**: Легкость поддержки и развития

Следуй этим правилам для создания надежной и масштабируемой системы управления скиллами.