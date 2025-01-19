# Dojo Wars: Requirements

## Project Overview

**Dojo Wars** is a Ubuntu terminal-based tool designed to streamline the process of practicing and organizing Codewars katas locally. It provides an easy interface to connect Codewars katas with a local repository, automatically managing the creation of files and folder structures required for each kata.

---

## Features

1. **Codewars Integration**:

   - Accepts a Codewars kata link via the terminal.
   - Scrapes the Codewars website to retrieve kata information in JSON format.

2. **Folder and File Generation**:
   - Creates a well-organized folder structure based on the kata's language, difficulty (kyu), and name.
   - Folder structure:

     ```tree
     katas
     ├── codewars
     │   ├── language (php, python, etc)
     │   │   ├── kyu (1 kyu, 2 kyu, etc)
     │   │   └── kata (kata name)
     │   │       ├── README.md (with the description of the kata)
     │   │       ├── test.php (with the tests)
     │   │       ├── solution.php (with the solution)
     ```

3. **Docker Integration**:
   - Supports running scripts and tests inside a Docker container for environment consistency and ease of setup.

4. **Extensibility**:
   - Modular design to enable potential IDE integration in the future.

---

## Requirements

### Functional Requirements

1. **Kata Retrieval**:
   - The system should prompt the user for a Codewars kata link.
   - The scraper should parse the link and retrieve the kata details as a JSON object.

2. **Folder Structure**:
   - The system should generate folders and files based on the following:
     - Programming language.
     - Kata difficulty (kyu level).
     - Kata name.
   - Files should include:
     - `README.md`: Containing the kata's description.
     - Test file (e.g., `test.php`): With predefined test cases for the kata.
     - Solution file (e.g., `solution.php`): Empty template for writing the solution.

3. **Script Execution**:
   - Users should be able to run the kata-related scripts (e.g., tests) using predefined Docker containers.

4. **Docker Container**:
   - The Docker environment should:
     - Support multiple programming languages (e.g., PHP, Python, etc.).
     - Include required dependencies for running the kata solutions and tests.

### Non-Functional Requirements

1. **Performance**:
   - JSON retrieval and file generation should occur in under 5 seconds.

2. **Scalability**:
   - The system should be easily extensible to support additional languages and features.

3. **Ease of Use**:
   - Simple and intuitive terminal commands.
   - Minimal setup for Docker environment.

4. **Error Handling**:
   - Informative error messages for:
     - Invalid Codewars links.
     - Missing dependencies.
     - Docker setup issues.

---

## Future Enhancements

1. **IDE Integration**:
   - Build extensions for popular IDEs (e.g., VSCode) to simplify the kata management process.

2. **Improved Kata Management**:
   - Enable searching and filtering katas from Codewars directly via the terminal.

3. **Advanced Testing**:
   - Support for additional test frameworks and customizable test cases.

---

## Development Setup

1. Clone the repository:

   ```bash
   git clone https://github.com/your-username/dojo-wars.git
   cd dojo-wars
   ```

2. Install dependencies:
   - Docker must be installed and running.

3. Run the application:

   ```bash
   make run
   ```

---

This structured approach will ensure the **Dojo Wars** project is functional, scalable, and easy to use. Let me know if you'd like to refine or add more details!