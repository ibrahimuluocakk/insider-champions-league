# Insider Champions League

A football championship league simulation web application built with Laravel backend and Next.js frontend, following Clean Architecture principles and the Service-Repository pattern.

## 🏆 Features

- **Team Management**: View teams with their power ratings and statistics
- **Fixture Generation**: Automatically generate league fixtures for all teams
- **Match Simulation**: Simulate matches week by week or all at once
- **League Table**: Real-time league standings with detailed statistics
- **Championship Predictions**: AI-powered predictions available after week 4
- **Responsive Design**: Modern UI with dark/light theme support
- **Modular Architecture**: Clean, maintainable, and testable code structure

## 🏗️ Architecture

### Backend (Laravel)
- **Clean Architecture** with Service-Repository pattern
- **Controllers**: Handle HTTP requests/responses only
- **Services**: Contain business logic with dependency injection
- **Repositories**: Interface-driven data access layer
- **Models**: Thin models with relationships and attributes
- **Requests**: Validation logic
- **Resources**: Response formatting

### Frontend (Next.js)
- **Modular Components**: Separate components for each feature
- **TypeScript**: Full type safety
- **Shadcn UI**: Modern component library
- **Responsive Design**: Mobile-first approach
- **Clean State Management**: React hooks with proper separation of concerns

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Node.js 20+ (for local development)
- PHP 8.2+ (for local development)

### Using Docker (Recommended)

1. **Clone the repository**
   ```bash
   git clone git@github.com:ibrahimuluocakk/insider-champions-league.git
   cd insider-champions-league
   ```

2. **Start the application**
   ```bash
   docker-compose up -d
   ```

3. **Access the application**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8001

## 📁 Project Structure

```
insider-champions-league/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/ # HTTP request handlers
│   │   │   ├── Requests/    # Validation logic
│   │   │   └── Resources/   # Response formatting
│   │   ├── Services/        # Business logic
│   │   ├── Repositories/    # Data access layer
│   │   └── Models/          # Eloquent models
│   ├── routes/
│   └── database/
├── frontend/                # Next.js Application
│   ├── app/                 # App Router pages
│   ├── components/          # Reusable components
│   │   ├── Teams.tsx
│   │   ├── Fixtures.tsx
│   │   ├── LeagueTable.tsx
│   │   ├── MatchSimulation.tsx
│   │   └── ChampionshipPredictions.tsx
│   ├── lib/                 # Utilities and API client
│   └── types/               # TypeScript definitions
└── docker-compose.yml       # Docker configuration
```

## 🎮 How to Use

1. **View Teams**: See all participating teams with their power ratings
2. **Generate Fixtures**: Click "Generate Fixtures" to create the league schedule
3. **Simulate Matches**: 
   - Use "Simulate Next Week" to play one week at a time
   - Use "Simulate All Remaining" to complete the entire season
4. **Monitor Progress**: Watch the league table update in real-time
5. **Championship Predictions**: View AI predictions after week 4

## 🧮 Simulation Algorithm

The match simulation uses a sophisticated algorithm that considers:
- **Team Power**: Base strength of each team
- **Home Advantage**: Additional boost for home teams
- **Random Factors**: Realistic unpredictability in football
- **Goal Calculation**: Realistic score generation based on team strengths

### Championship Predictions
- Available after week 4 when sufficient data exists
- Uses Monte Carlo simulation with 10,000+ iterations
- Considers remaining fixtures and current form
- Provides probability percentages for each team

## 🎨 UI Components

### Teams Component
- Displays team information with power ratings
- Shows total strength calculations
- Responsive card layout

### Fixtures Component
- Groups matches by week
- Shows match results and upcoming games
- Scrollable interface for large fixture lists

### League Table Component
- Interactive tooltips for column explanations
- Color-coded statistics (wins/draws/losses)
- Real-time updates after each simulation

### Match Simulation Component
- Control panel for all simulation actions
- Status messages and loading states
- Disabled states for logical flow

### Championship Predictions Component
- Detailed prediction breakdown
- Progress bars for visual probability representation
- Methodology information display

## 🔧 API Endpoints

- `GET /api/teams` - Get all teams
- `POST /api/fixtures` - Generate fixtures
- `GET /api/fixtures` - Get current fixtures
- `POST /api/simulation/next-week` - Simulate next week
- `POST /api/simulation/all` - Simulate all remaining matches
- `GET /api/league-table` - Get current standings
- `GET /api/championship-predictions` - Get championship predictions

## 🧪 Testing

### Backend Tests
```bash
cd backend
docker-compose exec -it backend sh
php artisan test
```
## 🛠️ Development

### Code Style
- **Laravel**: Follow PSR-12 standards
- **Next.js**: Use ESLint and Prettier configurations
- **TypeScript**: Strict mode enabled
- **Components**: Functional components with hooks

## 📋 Requirements Met

✅ **PHP/Laravel Backend**: Clean Architecture with OOP principles  
✅ **Modern Frontend**: Next.js with TypeScript  
✅ **Match Simulation**: Realistic algorithm considering team strengths  
✅ **League Table**: Real-time updates with complete statistics  
✅ **Championship Predictions**: Available after week 4 with detailed methodology  
✅ **Responsive Design**: Works on all device sizes  
✅ **Docker Support**: Easy deployment and development  
✅ **Modular Code**: Maintainable and testable architecture  

## 📄 License

This project is licensed under the MIT License.

## 🏅 Credits

Built with ❤️ using:
- [Laravel](https://laravel.com/) - Backend framework
- [Next.js](https://nextjs.org/) - Frontend framework
- [Shadcn UI](https://ui.shadcn.com/) - UI components
- [Tailwind CSS](https://tailwindcss.com/) - Styling
- [TypeScript](https://www.typescriptlang.org/) - Type safety 