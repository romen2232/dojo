# Dojo Wars 🥋

A powerful PHP tool to practice Codewars katas locally. Transform your coding practice by bringing Codewars katas into your preferred development environment, complete with automated setup, testing, and a professional development workflow.

## ✨ Features

- 🔄 Seamless kata scraping from Codewars
- 🏗️ Automated local kata environment setup
- 🐳 Containerized development environment
- 🧪 Professional testing infrastructure
- 🎨 Automated code style enforcement
- 📁 Organized kata management
- 🎯 Test-Driven Development workflow
- 🔷 Hexagonal Architecture implementation

## 🎓 Technical Excellence

### Hexagonal Architecture (Ports & Adapters)

The project implements the Hexagonal Architecture pattern, providing:

- **Domain Isolation**: Core business logic is completely isolated from external concerns
- **Dependency Inversion**: All dependencies point inward toward the domain
- **Pluggable Adapters**: Easy to swap implementations without affecting the core domain
- **Testability**: Domain logic can be tested without external dependencies

```
Domain Layer
├── Model/           # Pure domain entities
│   └── Kata.php    # Core domain concept
└── Port/           # Interface definitions
    └── KataRepositoryInterface.php

Application Layer
└── Service/        # Use cases implementation
    └── KataScraperService.php

Infrastructure Layer
└── Adapters/       # External implementations
    ├── Input/      # Driving adapters (CLI)
    └── Output/     # Driven adapters (HTTP, File System)
```

### Test-Driven Development (TDD)

The project strictly follows TDD principles with a comprehensive test suite:

1. **Red Phase**: Write failing tests first
   ```php
   public function testKataScraping(): void
   {
       $this->expectException(KataNotFoundException::class);
       $this->scraper->scrape('invalid-url');
   }
   ```

2. **Green Phase**: Implement minimum code to pass
   ```php
   public function scrape(string $url): Kata
   {
       if (!$this->validator->isValid($url)) {
           throw new KataNotFoundException();
       }
       // Implementation...
   }
   ```

3. **Refactor Phase**: Improve code while keeping tests green

### Clean Code Principles

- **SOLID** principles adherence
- **Small, focused classes** with single responsibilities
- **Immutable objects** for safer domain modeling
- **Value Objects** for domain concepts
- **Rich domain model** over anemic one

## 🎯 How It Works

When you find an interesting kata on Codewars, Dojo Wars helps you practice it locally in these steps:

1. **Kata Scraping**: Using the kata URL, the tool scrapes:
   - Kata description and instructions
   - Test cases
   - Initial solution template
   - Metadata (difficulty, tags, etc.)

2. **Local Setup**: The kata is organized in a clean structure:
```
katas/
└── codewars/
    └── php/
        └── level/              # Difficulty level (e.g., 4_kyu)
            └── decode_the_morse_code/    # Slugified kata name
                ├── README.md   # Kata description
                ├── .kata.json   # Kata metadata
                ├── solution.php # Your solution file
                └── test.php    # PHPUnit test cases
```

3. **Development Flow**:
   - Write your solution in `solution.php`
   - Run tests to verify your implementation
   - Use professional tools (linting, formatting)
   - Commit your progress to Git

## 🚀 Requirements

- Docker
- Docker Compose
- Make (optional, but recommended)

## 📦 Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd dojo
```

2. Install the project:
```bash
make install
```

This command sets up your development environment by:
- Building the Docker containers
- Installing PHP dependencies
- Setting up the kata directory structure

## 🛠️ Usage

### Kata Management

#### Start a New Kata

Found an interesting kata? Start practicing in seconds:

   ```bash
make kata URL="https://www.codewars.com/kata/your-kata-url"
```

This single command will:
1. Scrape the kata from Codewars
2. Create a properly structured directory
3. Set up tests and solution template
4. Generate a detailed README

#### Custom Directory Setup
```bash
make kata URL="https://www.codewars.com/kata/your-kata-url" DIR="custom/path"
```

### Development Commands

#### Container Management
```bash
make up      # Start the development environment
make down    # Stop containers
make shell   # Access PHP container shell
make restart # Restart the environment
```

#### Testing & Quality
   ```bash
make test           # Run test suite
make test-coverage  # Generate test coverage report
make fix-cs         # Fix code style issues
make lint           # Check code style
make validate       # Run all checks
```

## 🏗️ Project Structure

The project follows a strict layered architecture:

```
.
├── bin/                # Console commands
├── src/
│   ├── Application/   # Application services (Use cases)
│   │   └── Service/   # Orchestration of domain objects
│   ├── Domain/        # Core business rules
│   │   ├── Model/     # Enterprise business rules
│   │   ├── Port/      # Boundary interfaces
│   │   ├── Event/     # Domain events
│   │   └── Exception/ # Domain exceptions
│   └── Infrastructure/# External concerns
│       ├── Adapters/  # Implementation of ports
│       │   ├── Input/ # UI, CLI, API endpoints
│       │   └── Output/# Persistence, External services
│       └── Command/   # Console commands
├── tests/             # Comprehensive test suite
│   ├── Unit/         # Isolated tests
│   ├── Integration/  # Component interaction tests
│   └── E2E/          # End-to-end scenarios
└── docker/           # Container configuration
```

## 🔧 Development Stack

- PHP 8.1+ for modern language features
- Symfony Components:
  - Console for CLI interface
  - DomCrawler for kata scraping
- PHPUnit for robust testing
- PHP-CS-Fixer for consistent code style
- Docker for isolated development

## 🎭 Design Patterns Used

- **Repository Pattern**: For data access abstraction
- **Command Pattern**: In CLI commands implementation
- **Factory Pattern**: For object creation
- **Strategy Pattern**: For flexible algorithm implementations
- **Observer Pattern**: For domain events handling
- **Adapter Pattern**: For external service integration

## 🧪 Quality Assurance

- **Static Analysis**: PHPStan at max level
- **Mutation Testing**: Infection for test quality
- **Code Coverage**: 90%+ coverage requirement
- **Integration Tests**: Real HTTP requests with VCR
- **Behavioral Tests**: Behat for E2E scenarios
- **CI/CD Pipeline**: Automated quality checks

## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is open-source software licensed under the MIT license.

## 🌟 Why Dojo Wars?

- **Professional Development**: Practice katas in a real development environment
- **Version Control**: Track your progress with Git
- **Testing First**: Embrace TDD with pre-configured tests
- **Code Quality**: Maintain high standards with built-in tools
- **Organized Learning**: Keep your kata solutions structured and accessible

## 🎓 Learning Opportunities

This project serves as an excellent example of:

- **Domain-Driven Design**: Practical implementation of DDD concepts
- **Clean Architecture**: Real-world application of Uncle Bob's principles
- **SOLID Principles**: Concrete examples of all five principles
- **Testing Strategies**: Comprehensive testing approach at all levels
- **Modern PHP**: Latest language features and best practices
