# Contributing

Bug fixes and new features are welcome. Just make sure to open a [discussion](https://github.com/osteel/dime/discussions) before you take on any significant work to avoid disappointment.

## General

I went to some lengths to describe the implementation of Dime. This is a project I built in public and whose development process I documented on my [blog](https://tech.osteel.me/posts/building-a-php-cli-tool-using-ddd-and-event-sourcing-why "Building a PHP CLI tool using DDD and Event Sourcing: why?").

You may want to check out the following articles before you contribute:

* [The domain](https://tech.osteel.me/posts/building-a-php-cli-tool-using-ddd-and-event-sourcing-the-domain "Building a PHP CLI tool using DDD and Event Sourcing: the domain")
* [The model](https://tech.osteel.me/posts/building-a-php-cli-tool-using-ddd-and-event-sourcing-the-model "Building a PHP CLI tool using DDD and Event Sourcing: the model ")
* [Software design](https://tech.osteel.me/posts/building-a-php-cli-tool-using-ddd-and-event-sourcing-software-design "Building a PHP CLI tool using DDD and Event Sourcing: software design")

The following rules are also enforced through the [CI workflow](/.github/workflows/ci.yml):

* [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style guide
* [PHPStan](https://phpstan.org/) level 9
* 100% test coverage (using [Pest](https://pestphp.com/))

## Submitting changes

1. [Fork the repository](https://github.com/osteel/dime/fork)
2. Check out a new branch and name it to what you intend to do (use one branch per fix/feature)
3. Use meaningful commit messages
4. Open a draft pull request towards the `main` branch, following the description template
5. Make sure the CI workflow is successful before marking the PR as ready