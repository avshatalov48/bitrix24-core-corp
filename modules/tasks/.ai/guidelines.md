# Bitrix24 AI Assistance Guidelines

## Overview

Bitrix24 follows a modular architecture with strict domain boundaries. This enhanced guideline provides LLM agents with structured knowledge to effectively navigate and contribute to the codebase, with special attention to architectural patterns and cognitive interfaces.

---

## Architectural Principles for AI Assistance

### Module Interaction Framework
- **Boundary Enforcement**:
	- Modules are isolated domains with explicit integration points
	- Cross-module communication must use published APIs/events
	- Never suggest changes that violate module encapsulation

### Cognitive Mapping Strategy
- **Codebase Navigation**:
	- `/module/lib/V2/` indicates modern implementations
	- `Internal/` vs `Public/` denotes accessibility boundaries
	- `Provider/Command` pattern indicates CQRS implementation

### Decision Support Matrix
| Concern          | Tasks Module                 |
|------------------|------------------------------|
| Architecture     | CQRS (V2)                    |
| State Changes    | Command Handlers             |
| Data Access      | Providers + Repositories     |
| Validation       | Command Validation           |

---

## Module: `tasks`

### Architectural Blueprint

tasks/
├── lib/
│ ├── V2/
│ │ ├── Public/ # External Interface Layer
│ │ │ ├── Command/ # Write operations (CQRS)
│ │ │ └── Provider/ # Read operations (CQRS)
│ │ ├── Internal/ # Domain Core
│ │ │ ├── Model/ # Domain Entities
│ │ │ ├── Repository/ # Persistence Abstraction
│ │ │ └── Service/ # Business Logic
│ │ └── Infrastructure/ # Technical Implementation
│ │ └── Controller/ # API Endpoints
└── install/
└── js/tasks/v2/ # Vue3 Frontend

### CQRS Implementation Guide

1. **Command Flow**:
   API → Controller → Command → Handler → Service → Repository

2. **Query Flow**:
   API → Controller → Provider → Repository

3. **Transaction Boundaries**:
	- Commands are transactional units
	- Providers are read-only

### AI Development Patterns

**When implementing features:**
1. Identify if it's a command (state change) or query (data retrieval)
2. Place business logic in Internal/Service
3. Expose through Public/Command or Public/Provider
4. Add corresponding Vue component in install/js/tasks/v2/

**Example Implementation Template**:
```php
// New Command Handler
namespace Tasks\Lib\V2\Public\Command\Task;

class CreateDependencyHandler implements CommandHandlerInterface 
{
    public function __construct(
        private DependencyService $service,
        private Validator $validator
    ) {}

    public function handle(CreateDependencyCommand $command): Result
    {
        $this->validator->validate($command);
        return $this->service->createDependency(
            $command->getTaskId(),
            $command->getDependsOnId()
        );
    }
}
```

### Common Pitfalls to Avoid

- Mixing V1 and V2 architectures
- Placing business logic in controllers
- Bypassing command validation
- Direct database access outside repositories


### LLM-Specific Navigation Aids

### Codebase Semantics Map
| Path Pattern          | Semantic Meaning            | LLM Action                      |
|-----------------------|-----------------------------|---------------------------------|
| /V2/Public/Command/   | Write operations            | Implement command validation    |
| /V2/Internal/Service/ | Core business logic         | Add domain logic here           |
| /V2/Infrastructure/   | Technical implementations   | Interface with external systems |

---

### Context-Aware Development Matrix
| Task Type         | Location                     | Testing Approach                 |
|-------------------|------------------------------|----------------------------------|
| New Business Rule | Internal/Service             | Unit test domain logic           |
| API Endpoint      | Infrastructure/Controller    | Integration test with HTTP       |
| UI Component      | install/js/tasks/v2/         | Component tests + E2E            |

---

## Intelligent Assistance Protocol

1. **Requirement Analysis:**
- Classify as CRUD, report, or integration task
- Determine affected architectural layers

2. **Solution Generation:**
- Propose pattern-aligned implementations
- Include cross-cutting concerns (logging, validation)

3. **Validation Checklist:**
- Module boundaries respected
- Architecture pattern followed
- Proper layer separation
- Test coverage indicated

4. **Documentation Standard:**
## [Feature Name]
### Architectural Impact
- Affected Layers: [UI/API/Domain]
- Dependencies: [Modules/Services]

### Implementation Notes
1. Primary change location: `path/to/file`
2. Integration points: `event/listener`
3. Security considerations: [permissions/validation]

## Knowledge Maintenance
**Version Awareness:**
- Clearly mark suggestions as applicable to V1 or V2
- Prefer V2 implementations unless maintaining legacy code

**Learning Mechanism:**
- Maintain internal mapping of module experts
- Track architectural decisions in ADRs

**Feedback Integration:**
- Implement a confidence scoring system for suggestions
- Allow architectural pattern verification requests