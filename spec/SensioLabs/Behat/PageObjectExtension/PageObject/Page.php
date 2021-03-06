<?php

namespace spec\SensioLabs\Behat\PageObjectExtension\PageObject;

use Behat\Mink\Exception\DriverException;
use PHPSpec2\ObjectBehavior;
use SensioLabs\Behat\PageObjectExtension\PageObject\Exception\PathNotProvidedException;
use SensioLabs\Behat\PageObjectExtension\PageObject\Exception\UnexpectedPageException;
use SensioLabs\Behat\PageObjectExtension\PageObject\Page as BasePage;

class MyPage extends BasePage
{
    protected $path = '/employees/{employee}';

    public function callGetPage($name)
    {
        return $this->getPage($name);
    }

    public function callGetElement($name)
    {
        return $this->getElement($name);
    }

    public function callGetName()
    {
        return $this->getName();
    }
}

class MyPageWithoutPath extends BasePage
{
}

class MyPageWithValidation extends MyPage
{
    protected function verifyPage()
    {
        throw new UnexpectedPageException('Expected to be on "MyPage" but found "Homepage" instead');
    }
}

class Page extends ObjectBehavior
{
    /**
     * @param \Behat\Mink\Session                                                $session
     * @param \SensioLabs\Behat\PageObjectExtension\Context\PageFactoryInterface $factory
     */
    function let($session, $factory)
    {
        // until we have proper abstract class support in PHPSpec2
        $this->beAnInstanceOf('spec\SensioLabs\Behat\PageObjectExtension\PageObject\MyPage');
        $this->beConstructedWith($session, $factory);
    }

    function it_should_be_a_document_element()
    {
        $this->shouldHaveType('Behat\Mink\Element\DocumentElement');
    }

    function it_opens_a_relative_path($session)
    {
        $session->visit('/employees/13')->shouldBeCalled();
        $session->getStatusCode()->willReturn(200);

        $this->open(array('employee' => 13))->shouldReturn($this);
    }

    function it_prepends_base_url($session, $factory)
    {
        $this->beConstructedWith($session, $factory, array('base_url' => 'http://behat.dev/'));

        $session->visit('http://behat.dev/employees/13')->shouldBeCalled();
        $session->getStatusCode()->willReturn(200);

        $this->open(array('employee' => 13))->shouldReturn($this);
    }

    function it_cleans_up_slashes($session, $factory)
    {
        $this->beConstructedWith($session, $factory, array('base_url' => 'http://behat.dev/'));

        $session->visit('http://behat.dev/employees/13')->shouldBeCalled();
        $session->getStatusCode()->willReturn(200);

        $this->open(array('employee' => 13))->shouldReturn($this);
    }

    function it_leaves_placeholders_if_not_provided($session)
    {
        $session->visit('/employees/{employee}')->shouldBeCalled();
        $session->getStatusCode()->willReturn(200);

        $this->open()->shouldReturn($this);
    }

    function it_requires_path_to_open_a_page($session, $factory)
    {
        $this->beAnInstanceOf('spec\SensioLabs\Behat\PageObjectExtension\PageObject\MyPageWithoutPath');
        $this->beConstructedWith($session, $factory);

        $this->shouldThrow(new PathNotProvidedException('You must add a path property to your page object'))
            ->duringOpen();
    }

    function it_verifies_client_error_status_code_if_available($session, $factory)
    {
        $session->visit('/employees/13')->shouldBeCalled();
        $session->getStatusCode()->willReturn(404);
        $session->getCurrentUrl()->willReturn('/employees/13');

        $this->shouldThrow(new UnexpectedPageException('Could not open the page: "/employees/13". Received an error status code: 404'))
            ->duringOpen(array('employee' => 13));
    }

    function it_verifies_server_error_status_code_if_available($session)
    {
        $session->visit('/employees/13')->shouldBeCalled();
        $session->getStatusCode()->willReturn(500);
        $session->getCurrentUrl()->willReturn('/employees/13');

        $this->shouldThrow(new UnexpectedPageException('Could not open the page: "/employees/13". Received an error status code: 500'))
            ->duringOpen(array('employee' => 13));
    }

    function it_skips_status_code_check_if_driver_does_not_support_it($session)
    {
        $session->visit('/employees/13')->shouldBeCalled();
        $session->getStatusCode()->willThrow(new DriverException(''));

        $this->open(array('employee' => 13))->shouldReturn($this);
    }

    function it_optionally_verifies_the_page($session, $factory)
    {
        $this->beAnInstanceOf('spec\SensioLabs\Behat\PageObjectExtension\PageObject\MyPageWithValidation');
        $this->beConstructedWith($session, $factory);

        $session->visit('/employees/13')->shouldBeCalled();
        $session->getStatusCode()->willReturn(200);

        $this->shouldThrow(new UnexpectedPageException('Expected to be on "MyPage" but found "Homepage" instead'))->duringOpen(array('employee' => 13));
    }

    function it_gives_clear_feedback_if_method_is_invalid($session, $factory)
    {
        $this->beConstructedWith($session, $factory, array('base_url' => 'http://behat.dev/'));

        $this->shouldThrow(new \BadMethodCallException('"search" method is not available on the MyPage'))->during('search');
    }

    function it_creates_a_page($factory, $page)
    {
        $page->beAnInstanceOf('SensioLabs\Behat\PageObjectExtension\PageObject\Page');

        $factory->createPage('Home')->willReturn($page);

        $this->callGetPage('Home')->shouldReturn($page);
    }

    function it_creates_an_element($factory, $element)
    {
        $element->beAnInstanceOf('SensioLabs\Behat\PageObjectExtension\PageObject\Element');

        $factory->createElement('Navigation')->willReturn($element);

        $this->callGetElement('Navigation')->shouldReturn($element);
    }

    function it_returns_the_page_name()
    {
        $this->callGetName()->shouldReturn('MyPage');
    }
}
